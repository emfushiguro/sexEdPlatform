import './bootstrap';
import Alpine from 'alpinejs';
import './toast'; // Toast notification system

window.Alpine = Alpine;

// Global modal state store
Alpine.store('modals', {
    quizModal: false,
    
    openQuizModal() {
        this.quizModal = true;
    },
    
    closeQuizModal() {
        this.quizModal = false;
    }
});

Alpine.start();
