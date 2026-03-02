import './bootstrap';
import { initFlowbite } from 'flowbite';

// Apply dark mode before first paint to prevent FOUC
(function () {
    if (
        localStorage.getItem('theme') === 'dark' ||
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
})();

// Generate and persist device UUID for audit logging
(function () {
    if (!localStorage.getItem('device_uuid')) {
        const uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
        localStorage.setItem('device_uuid', uuid);
    }
    window.deviceUuid = localStorage.getItem('device_uuid');
})();

// Re-init Flowbite after Livewire navigations
document.addEventListener('livewire:navigated', () => initFlowbite());
document.addEventListener('livewire:initialized', () => initFlowbite());
