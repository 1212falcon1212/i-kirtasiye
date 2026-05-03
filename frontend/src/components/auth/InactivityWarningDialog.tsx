'use client';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Clock, LogOut } from 'lucide-react';

interface InactivityWarningDialogProps {
  open: boolean;
  remainingSeconds: number;
  onContinue: () => void;
  onLogout: () => void;
}

export function InactivityWarningDialog({
  open,
  remainingSeconds,
  onContinue,
  onLogout,
}: InactivityWarningDialogProps) {
  return (
    <Dialog open={open} onOpenChange={(isOpen) => { if (isOpen) return; onContinue(); }}>
      <DialogContent className="sm:max-w-md" onInteractOutside={(e) => e.preventDefault()}>
        <DialogHeader>
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
            <Clock className="h-7 w-7 text-amber-600" />
          </div>
          <DialogTitle className="text-center text-lg">
            Oturumunuz sona erecek
          </DialogTitle>
          <DialogDescription className="text-center">
            Uzun süredir işlem yapmadınız. Güvenliğiniz için oturumunuz{' '}
            <span className="font-bold text-amber-600 tabular-nums">
              {remainingSeconds}
            </span>{' '}
            saniye içinde sonlandırılacak.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
          <Button
            variant="outline"
            onClick={onLogout}
            className="text-red-500 border-red-200 hover:bg-red-50 hover:text-red-600"
          >
            <LogOut className="mr-2 h-4 w-4" />
            Çıkış Yap
          </Button>
          <Button
            onClick={onContinue}
            className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white"
          >
            Devam Et
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
