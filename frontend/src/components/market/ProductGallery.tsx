'use client';

import { useState, useRef } from 'react';
import Image from 'next/image';
import { Box, ZoomIn, ZoomOut, X, ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';

interface ProductGalleryProps {
    images: string[];
    productName: string;
    className?: string;
}

export function ProductGallery({ images, productName, className }: ProductGalleryProps) {
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [isZoomed, setIsZoomed] = useState(false);
    const [showLightbox, setShowLightbox] = useState(false);
    const [zoomPosition, setZoomPosition] = useState({ x: 50, y: 50 });
    const imageRef = useRef<HTMLDivElement>(null);

    const hasImages = images && images.length > 0;
    const currentImage = hasImages ? images[selectedIndex] : null;

    const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
        if (!imageRef.current || !isZoomed) return;

        const rect = imageRef.current.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;

        setZoomPosition({ x, y });
    };

    const handlePrevious = () => {
        setSelectedIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
    };

    const handleNext = () => {
        setSelectedIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
    };

    const getImageUrl = (image: string) => {
        if (image.startsWith('http')) return image;
        const apiUrl = process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || '';
        return `${apiUrl}/storage/${image}`;
    };

    return (
        <div className={cn('space-y-4', className)}>
            {/* Main Image Container */}
            <div
                ref={imageRef}
                className={cn(
                    'relative aspect-square bg-white rounded-lg border border-slate-200 overflow-hidden',
                    'group cursor-zoom-in transition-all duration-300',
                    isZoomed && 'cursor-zoom-out'
                )}
                onMouseEnter={() => setIsZoomed(true)}
                onMouseLeave={() => setIsZoomed(false)}
                onMouseMove={handleMouseMove}
                onClick={() => hasImages && setShowLightbox(true)}
            >
                {currentImage ? (
                    <>
                        {/* Normal Image */}
                        <div
                            className={cn(
                                'absolute inset-0 transition-opacity duration-300',
                                isZoomed ? 'opacity-0' : 'opacity-100'
                            )}
                        >
                            <Image
                                src={getImageUrl(currentImage)}
                                alt={productName}
                                fill
                                className="object-contain p-6"
                                sizes="(max-width: 768px) 100vw, 400px"
                                priority
                            />
                        </div>

                        {/* Zoomed Image */}
                        <div
                            className={cn(
                                'absolute inset-0 transition-opacity duration-300',
                                isZoomed ? 'opacity-100' : 'opacity-0'
                            )}
                            style={{
                                backgroundImage: `url(${getImageUrl(currentImage)})`,
                                backgroundPosition: `${zoomPosition.x}% ${zoomPosition.y}%`,
                                backgroundSize: '200%',
                                backgroundRepeat: 'no-repeat',
                            }}
                        />

                        {/* Zoom Indicator */}
                        <div
                            className={cn(
                                'absolute bottom-4 right-4 flex items-center gap-2 px-3 py-1.5 bg-black/70 text-white text-xs rounded-full',
                                'opacity-0 group-hover:opacity-100 transition-opacity duration-200'
                            )}
                        >
                            <ZoomIn className="w-3.5 h-3.5" />
                            <span>Zoom</span>
                        </div>

                        {/* Navigation Arrows - Only show if multiple images */}
                        {images.length > 1 && (
                            <>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className={cn(
                                        'absolute left-2 top-1/2 -translate-y-1/2 h-10 w-10',
                                        'bg-white/90 hover:bg-white shadow-lg border-0',
                                        'opacity-0 group-hover:opacity-100 transition-all duration-200'
                                    )}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handlePrevious();
                                    }}
                                >
                                    <ChevronLeft className="h-5 w-5" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className={cn(
                                        'absolute right-2 top-1/2 -translate-y-1/2 h-10 w-10',
                                        'bg-white/90 hover:bg-white shadow-lg border-0',
                                        'opacity-0 group-hover:opacity-100 transition-all duration-200'
                                    )}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleNext();
                                    }}
                                >
                                    <ChevronRight className="h-5 w-5" />
                                </Button>
                            </>
                        )}
                    </>
                ) : (
                    /* Placeholder for no image */
                    <div className="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100">
                        <div className="p-6 rounded-full bg-slate-200/50 mb-4">
                            <Box className="h-16 w-16 text-slate-400" />
                        </div>
                        <span className="text-sm text-slate-500 font-medium">Gorsel mevcut degil</span>
                    </div>
                )}

                {/* Image Counter Badge */}
                {images.length > 1 && (
                    <div className="absolute top-4 left-4 px-3 py-1 bg-black/70 text-white text-xs font-medium rounded-full">
                        {selectedIndex + 1} / {images.length}
                    </div>
                )}
            </div>

            {/* Thumbnail Gallery */}
            {images.length > 1 && (
                <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                    {images.map((image, index) => (
                        <button
                            key={index}
                            onClick={() => setSelectedIndex(index)}
                            className={cn(
                                'relative flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all duration-200',
                                selectedIndex === index
                                    ? 'border-blue-500 ring-2 ring-blue-500/20'
                                    : 'border-slate-200 hover:border-slate-300'
                            )}
                        >
                            <Image
                                src={getImageUrl(image)}
                                alt={`${productName} - ${index + 1}`}
                                fill
                                className="object-cover"
                                sizes="64px"
                            />
                        </button>
                    ))}
                </div>
            )}

            {/* Lightbox Dialog */}
            <Dialog open={showLightbox} onOpenChange={setShowLightbox}>
                <DialogContent className="max-w-4xl w-full h-[90vh] p-0 bg-black/95 border-0">
                    <DialogTitle className="sr-only">{productName} - Resim Galerisi</DialogTitle>
                    {/* Close Button */}
                    <Button
                        variant="ghost"
                        size="icon"
                        className="absolute top-4 right-4 z-50 text-white hover:bg-white/20 rounded-full"
                        onClick={() => setShowLightbox(false)}
                    >
                        <X className="h-6 w-6" />
                    </Button>

                    {/* Navigation */}
                    {images.length > 1 && (
                        <>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="absolute left-4 top-1/2 -translate-y-1/2 z-50 h-12 w-12 text-white hover:bg-white/20 rounded-full"
                                onClick={handlePrevious}
                            >
                                <ChevronLeft className="h-8 w-8" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="absolute right-4 top-1/2 -translate-y-1/2 z-50 h-12 w-12 text-white hover:bg-white/20 rounded-full"
                                onClick={handleNext}
                            >
                                <ChevronRight className="h-8 w-8" />
                            </Button>
                        </>
                    )}

                    {/* Lightbox Image */}
                    {currentImage && (
                        <div className="relative w-full h-full flex items-center justify-center p-12">
                            <Image
                                src={getImageUrl(currentImage)}
                                alt={productName}
                                fill
                                className="object-contain"
                                sizes="100vw"
                            />
                        </div>
                    )}

                    {/* Thumbnail Strip */}
                    {images.length > 1 && (
                        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 p-2 bg-black/50 rounded-lg backdrop-blur-sm">
                            {images.map((image, index) => (
                                <button
                                    key={index}
                                    onClick={() => setSelectedIndex(index)}
                                    className={cn(
                                        'relative w-12 h-12 rounded-md overflow-hidden border-2 transition-all',
                                        selectedIndex === index
                                            ? 'border-white opacity-100'
                                            : 'border-transparent opacity-50 hover:opacity-75'
                                    )}
                                >
                                    <Image
                                        src={getImageUrl(image)}
                                        alt={`${productName} - ${index + 1}`}
                                        fill
                                        className="object-cover"
                                        sizes="48px"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}
