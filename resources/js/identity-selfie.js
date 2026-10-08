export function createIdentitySelfie(dependencies = {}) {
    const mediaDevices = dependencies.mediaDevices === undefined ? globalThis.navigator?.mediaDevices : dependencies.mediaDevices;
    const createCanvas = dependencies.createCanvas === undefined ? () => globalThis.document?.createElement('canvas') : dependencies.createCanvas;
    const url = dependencies.url ?? globalThis.URL;
    const Transfer = dependencies.DataTransfer === undefined ? globalThis.DataTransfer : dependencies.DataTransfer;
    const ImageFile = dependencies.File ?? globalThis.File;

    return {
        stream: null,
        cameraActive: false,
        cameraError: '',
        status: '',
        previewUrl: null,
        candidate: null,
        photoReady: false,
        uploadAvailable: true,
        requestId: 0,

        stopCamera() {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
            this.cameraActive = false;
            if (this.$refs?.video) this.$refs.video.srcObject = null;
        },
        clearPreview() {
            if (this.previewUrl) url?.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
            this.candidate = null;
        },
        clearSelection() {
            if (this.$refs?.selfieInput) this.$refs.selfieInput.value = '';
            this.photoReady = false;
            this.clearPreview();
        },
        async startCamera() {
            this.requestId += 1;
            const request = this.requestId;
            this.stopCamera();
            this.clearSelection();
            this.cameraError = '';
            if (!mediaDevices?.getUserMedia || !createCanvas || !Transfer || !ImageFile) {
                this.cameraError = 'Camera capture is unavailable. Please upload a selfie instead.';
                return;
            }
            try {
                const stream = await mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                if (request !== this.requestId) {
                    stream.getTracks().forEach((track) => track.stop());
                    return;
                }
                this.stream = stream;
                if (!this.$refs?.video) throw new Error('Video preview unavailable');
                this.$refs.video.srcObject = stream;
                await this.$refs.video.play();
                if (request !== this.requestId) return;
                this.cameraActive = true;
                this.status = 'Camera ready. Capture your selfie.';
            } catch {
                if (request !== this.requestId) return;
                this.stopCamera();
                this.cameraError = 'Camera could not start. Please upload a selfie instead.';
            }
        },
        async capturePhoto() {
            if (!this.cameraActive || !this.stream) return;
            const request = this.requestId;
            try {
                const video = this.$refs.video;
                if (!video.videoWidth || !video.videoHeight || !this.stream.getTracks().some((track) => track.readyState !== 'ended')) {
                    throw new Error('Camera disconnected');
                }
                const canvas = createCanvas();
                if (!canvas?.getContext || !canvas?.toBlob) throw new Error('Canvas unavailable');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const context = canvas.getContext('2d');
                if (!context) throw new Error('Canvas unavailable');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                if (request !== this.requestId) return;
                if (!blob) throw new Error('Capture failed');
                this.clearPreview();
                this.candidate = new ImageFile([blob], 'selfie.jpg', { type: 'image/jpeg' });
                this.previewUrl = url.createObjectURL(this.candidate);
                this.status = 'Photo captured. Review it, then choose Use Photo.';
            } catch {
                if (request === this.requestId) this.cameraError = 'Photo capture failed. Please upload a selfie instead.';
            } finally {
                if (request === this.requestId) this.stopCamera();
            }
        },
        usePhoto() {
            if (!this.candidate) return;
            try {
                const transfer = new Transfer();
                transfer.items.add(this.candidate);
                this.$refs.selfieInput.files = transfer.files;
                this.photoReady = true;
                this.status = 'Selfie selected. It will be sent when you submit for review.';
                this.cameraError = '';
            } catch {
                this.cameraError = 'Could not select the captured photo. Please upload a selfie instead.';
            }
        },
        async retakePhoto() {
            this.clearSelection();
            await this.startCamera();
        },
        replacePhoto() {
            this.requestId += 1;
            this.stopCamera();
            this.clearSelection();
            this.status = 'Choose a replacement selfie to upload.';
            this.$refs?.selfieInput?.click?.();
        },
        uploadSelected(event) {
            this.requestId += 1;
            this.stopCamera();
            this.clearPreview();
            const file = event?.target?.files?.[0];
            this.photoReady = Boolean(file);
            if (file && url?.createObjectURL) this.previewUrl = url.createObjectURL(file);
            this.status = file ? 'Selfie selected. It will be sent when you submit for review.' : '';
            this.cameraError = '';
        },
        cancel() {
            this.requestId += 1;
            this.stopCamera();
            this.clearSelection();
            this.cameraError = '';
            this.status = 'Selfie selection cleared.';
        },
        destroy() {
            this.requestId += 1;
            this.stopCamera();
            this.clearPreview();
        },
    };
}
