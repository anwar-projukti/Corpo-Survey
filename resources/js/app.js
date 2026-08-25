import './bootstrap';
import Alpine from 'alpinejs';
import themeStore from './stores/theme';

window.Alpine = Alpine;

Alpine.store('theme', themeStore());

Alpine.start();
