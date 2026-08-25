import './bootstrap';
import Alpine from 'alpinejs';
import themeStore from './stores/theme';
import registerSortable from './surveys/sortable';
import surveyBuilder from './surveys/builder';
import surveysApi from './surveys/api';

window.Alpine = Alpine;
window.surveysApi = surveysApi;

Alpine.store('theme', themeStore());
Alpine.data('surveyBuilder', surveyBuilder);
registerSortable(Alpine);

Alpine.start();
