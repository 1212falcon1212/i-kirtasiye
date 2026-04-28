'use client';

import { useEffect, useRef, useState } from 'react';
import { BrowserMultiFormatReader, IScannerControls } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';
import { Button } from '@/components/ui/button';
import { X, Camera, Zap, ZapOff, ImageUp, Loader2 } from 'lucide-react';

interface BarcodeScannerProps {
    onScan: (decodedText: string) => void;
    onClose: () => void;
}

interface NativeBarcodeDetector {
    detect(source: CanvasImageSource): Promise<Array<{ rawValue: string; format: string }>>;
}
interface NativeBarcodeDetectorCtor {
    new (options?: { formats?: string[] }): NativeBarcodeDetector;
    getSupportedFormats?: () => Promise<string[]>;
}

const NATIVE_FORMATS = [
    'ean_13',
    'ean_8',
    'upc_a',
    'upc_e',
    'code_128',
    'code_39',
    'code_93',
    'itf',
    'codabar',
    'qr_code',
    'data_matrix',
];

export function BarcodeScanner({ onScan, onClose }: BarcodeScannerProps) {
    const [error, setError] = useState<string | null>(null);
    const [hasScanned, setHasScanned] = useState(false);
    const [torchOn, setTorchOn] = useState(false);
    const [torchSupported, setTorchSupported] = useState(false);
    const [decodingPhoto, setDecodingPhoto] = useState(false);
    const videoRef = useRef<HTMLVideoElement | null>(null);
    const controlsRef = useRef<IScannerControls | null>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const rafRef = useRef<number | null>(null);
    const autoCaptureIntervalRef = useRef<number | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const handleResultRef = useRef<(value: string) => void>(() => undefined);

    useEffect(() => {
        let isMounted = true;
        const scannedRef = { current: false };

        const handleResult = (value: string) => {
            if (scannedRef.current || !isMounted) return;
            scannedRef.current = true;
            setHasScanned(true);
            onScan(value);
        };
        handleResultRef.current = handleResult;

        const initStream = async () => {
            const video = videoRef.current;
            if (!video) return null;

            const stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                    // @ts-expect-error focusMode non-standard
                    advanced: [{ focusMode: 'continuous' }, { focusMode: 'auto' }],
                },
            });

            video.srcObject = stream;
            await video.play().catch(() => undefined);
            streamRef.current = stream;

            const track = stream.getVideoTracks()[0];
            if (track) {
                const caps = track.getCapabilities?.();
                if (caps && 'torch' in caps) {
                    setTorchSupported(true);
                }
                try {
                    await track.applyConstraints({
                        // @ts-expect-error focusMode non-standard
                        advanced: [{ focusMode: 'continuous' }],
                    });
                } catch {
                    // ignore
                }
            }
            return stream;
        };

        const startNative = async (detectorCtor: NativeBarcodeDetectorCtor): Promise<boolean> => {
            try {
                let supportedFormats: string[] = NATIVE_FORMATS;
                try {
                    const list = await detectorCtor.getSupportedFormats?.();
                    if (list && list.length > 0) {
                        supportedFormats = NATIVE_FORMATS.filter((f) => list.includes(f));
                    }
                } catch {
                    // ignore — use defaults
                }

                const detector = new detectorCtor({ formats: supportedFormats });

                const loop = async () => {
                    if (!isMounted || scannedRef.current) return;
                    const video = videoRef.current;
                    if (video && video.readyState >= 2) {
                        try {
                            const barcodes = await detector.detect(video);
                            if (barcodes.length > 0) {
                                handleResult(barcodes[0].rawValue);
                                return;
                            }
                        } catch {
                            // continue
                        }
                    }
                    rafRef.current = requestAnimationFrame(loop);
                };
                loop();
                return true;
            } catch (err) {
                console.warn('Native BarcodeDetector failed, falling back to ZXing', err);
                return false;
            }
        };

        const startZXing = async () => {
            const video = videoRef.current;
            if (!video) return;

            const hints = new Map();
            hints.set(DecodeHintType.POSSIBLE_FORMATS, [
                BarcodeFormat.EAN_13,
                BarcodeFormat.EAN_8,
                BarcodeFormat.UPC_A,
                BarcodeFormat.UPC_E,
                BarcodeFormat.CODE_128,
                BarcodeFormat.CODE_39,
                BarcodeFormat.CODE_93,
                BarcodeFormat.ITF,
                BarcodeFormat.CODABAR,
                BarcodeFormat.QR_CODE,
                BarcodeFormat.DATA_MATRIX,
            ]);
            hints.set(DecodeHintType.TRY_HARDER, true);

            const reader = new BrowserMultiFormatReader(hints, {
                delayBetweenScanAttempts: 80,
                delayBetweenScanSuccess: 500,
            });

            const controls = await reader.decodeFromVideoElement(video, (result, _err, ctrl) => {
                if (!isMounted) return;
                if (result) {
                    ctrl.stop();
                    handleResult(result.getText());
                }
            });
            controlsRef.current = controls;
        };

        const startServerAutoCapture = () => {
            const apiBase = process.env.NEXT_PUBLIC_API_URL || '/api';
            const url = `${apiBase}/barcode/decode`;
            let inFlight = false;

            const captureOnce = async () => {
                if (!isMounted || scannedRef.current) return;
                const video = videoRef.current;
                if (!video || video.readyState < 2 || video.videoWidth === 0) {
                    return;
                }
                if (inFlight) return;
                inFlight = true;

                try {
                    const canvas = document.createElement('canvas');
                    const targetWidth = Math.min(video.videoWidth, 1280);
                    const scale = targetWidth / video.videoWidth;
                    canvas.width = Math.round(video.videoWidth * scale);
                    canvas.height = Math.round(video.videoHeight * scale);
                    const ctx = canvas.getContext('2d');
                    if (!ctx) return;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const blob = await new Promise<Blob | null>((resolve) => {
                        canvas.toBlob(resolve, 'image/jpeg', 0.85);
                    });
                    if (!blob || !isMounted || scannedRef.current) return;

                    const formData = new FormData();
                    formData.append('image', blob, 'frame.jpg');
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: { Accept: 'application/json' },
                    });
                    if (!isMounted || scannedRef.current) return;
                    if (response.ok) {
                        const payload = await response.json();
                        if (payload.barcode) {
                            handleResult(payload.barcode);
                        }
                    }
                } catch (err) {
                    console.warn('Auto-capture error:', err);
                } finally {
                    inFlight = false;
                }
            };

            const intervalId = window.setInterval(captureOnce, 1500);
            captureOnce();
            return intervalId;
        };

        const start = async () => {
            try {
                await initStream();
                const w = window as unknown as { BarcodeDetector?: NativeBarcodeDetectorCtor };
                const nativeOk = w.BarcodeDetector ? await startNative(w.BarcodeDetector) : false;
                if (!nativeOk) {
                    autoCaptureIntervalRef.current = startServerAutoCapture();
                    try {
                        await startZXing();
                    } catch {
                        // ZXing başlamazsa auto-capture yine de çalışır
                    }
                }
            } catch (err) {
                if (isMounted) {
                    const msg = err instanceof Error ? err.message : 'Bilinmeyen hata';
                    setError(`Kameraya erişilemedi: ${msg}`);
                    console.error('BarcodeScanner error:', err);
                }
            }
        };

        start();

        return () => {
            isMounted = false;
            if (rafRef.current !== null) {
                cancelAnimationFrame(rafRef.current);
                rafRef.current = null;
            }
            if (autoCaptureIntervalRef.current !== null) {
                window.clearInterval(autoCaptureIntervalRef.current);
                autoCaptureIntervalRef.current = null;
            }
            controlsRef.current?.stop();
            controlsRef.current = null;
            streamRef.current?.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        };
    }, [onScan]);

    const decodeFromFile = async (file: File) => {
        setDecodingPhoto(true);
        setError(null);
        try {
            const formData = new FormData();
            formData.append('image', file);

            const apiBase = process.env.NEXT_PUBLIC_API_URL || '/api';
            const response = await fetch(`${apiBase}/barcode/decode`, {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
            });

            const payload = (await response.json().catch(() => ({}))) as {
                barcode?: string | null;
                message?: string;
            };

            if (response.ok && payload.barcode) {
                handleResultRef.current(payload.barcode);
                return;
            }

            setError(payload.message || 'Barkod okunamadı. Daha net bir fotoğraf deneyin.');
            setTimeout(() => setError(null), 4000);
        } catch (err) {
            console.error('Photo decode failed:', err);
            setError('Sunucuya ulaşılamadı. İnternetinizi kontrol edin.');
            setTimeout(() => setError(null), 4000);
        } finally {
            setDecodingPhoto(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const toggleTorch = async () => {
        const track = streamRef.current?.getVideoTracks()[0];
        if (!track) return;
        try {
            await track.applyConstraints({
                // @ts-expect-error torch not in standard typings
                advanced: [{ torch: !torchOn }],
            });
            setTorchOn((v) => !v);
        } catch (err) {
            console.error('Torch toggle failed:', err);
        }
    };

    return (
        <div className="fixed inset-0 z-[100] bg-black flex flex-col items-center justify-center">
            <Button
                variant="ghost"
                className="absolute top-4 right-4 text-white hover:bg-white/20 z-50"
                onClick={onClose}
            >
                <X className="w-8 h-8" />
            </Button>

            {torchSupported && (
                <Button
                    variant="ghost"
                    className="absolute top-4 left-4 text-white hover:bg-white/20 z-50"
                    onClick={toggleTorch}
                >
                    {torchOn ? <ZapOff className="w-7 h-7" /> : <Zap className="w-7 h-7" />}
                </Button>
            )}

            <div className="relative w-full max-w-2xl aspect-[4/3] bg-black overflow-hidden">
                <video
                    ref={videoRef}
                    className="w-full h-full object-cover"
                    playsInline
                    muted
                    autoPlay
                />

                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div className="relative w-[85%] h-[35%] border-2 border-white/90 rounded-lg">
                        <div className="absolute inset-x-0 top-1/2 h-[2px] bg-red-500/80 shadow-[0_0_8px_rgba(239,68,68,0.8)]" />
                        <span className="absolute -top-1 -left-1 w-5 h-5 border-t-4 border-l-4 border-emerald-400 rounded-tl" />
                        <span className="absolute -top-1 -right-1 w-5 h-5 border-t-4 border-r-4 border-emerald-400 rounded-tr" />
                        <span className="absolute -bottom-1 -left-1 w-5 h-5 border-b-4 border-l-4 border-emerald-400 rounded-bl" />
                        <span className="absolute -bottom-1 -right-1 w-5 h-5 border-b-4 border-r-4 border-emerald-400 rounded-br" />
                    </div>
                </div>

                {hasScanned && (
                    <div className="absolute inset-0 bg-emerald-500/30 flex items-center justify-center">
                        <div className="bg-emerald-500 text-white px-4 py-2 rounded-full font-semibold">
                            Barkod okundu!
                        </div>
                    </div>
                )}
            </div>

            {error ? (
                <div className="mt-4 px-4 py-3 bg-red-600/95 text-white rounded-lg text-sm max-w-md text-center backdrop-blur-sm flex items-center gap-2">
                    <Camera className="w-4 h-4 flex-shrink-0" />
                    <span>{error}</span>
                </div>
            ) : (
                <div className="mt-4 text-white text-center opacity-80 bg-black/50 px-4 py-2 rounded-full backdrop-blur-sm text-sm">
                    Barkodu kırmızı çizgiye yatay hizalayın
                </div>
            )}

            <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                capture="environment"
                className="hidden"
                onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) decodeFromFile(file);
                }}
            />
            <input
                type="file"
                accept="image/*"
                id="barcode-gallery-input"
                className="hidden"
                onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) decodeFromFile(file);
                }}
            />
            <div className="mt-4 flex gap-2 flex-wrap justify-center">
                <Button
                    onClick={() => fileInputRef.current?.click()}
                    disabled={decodingPhoto}
                    className="bg-white text-black hover:bg-white/90 font-semibold px-5 py-3 rounded-full shadow-lg"
                >
                    {decodingPhoto ? (
                        <>
                            <Loader2 className="w-5 h-5 mr-2 animate-spin" />
                            Çözümleniyor...
                        </>
                    ) : (
                        <>
                            <Camera className="w-5 h-5 mr-2" />
                            Fotoğraf çek
                        </>
                    )}
                </Button>
                <Button
                    onClick={() => document.getElementById('barcode-gallery-input')?.click()}
                    disabled={decodingPhoto}
                    variant="outline"
                    className="bg-transparent border-white text-white hover:bg-white/20 font-semibold px-5 py-3 rounded-full"
                >
                    <ImageUp className="w-5 h-5 mr-2" />
                    Galeriden seç
                </Button>
            </div>
        </div>
    );
}
