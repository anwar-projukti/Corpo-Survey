import './bootstrap';
import Alpine from 'alpinejs';
import themeStore from './stores/theme';
import registerSortable from './surveys/sortable';
import surveyBuilder from './surveys/builder';
import surveysApi from './surveys/api';
import publicSurveyForm from './surveys/public-form';
import twoFactorSetup from './two-factor-setup';
import fieldSurveyForm from './field/field-form';
import fieldSurveysIndex from './field/surveys-index';

window.Alpine = Alpine;
window.surveysApi = surveysApi;

Alpine.store('theme', themeStore());
Alpine.data('surveyBuilder', surveyBuilder);
Alpine.data('publicSurveyForm', publicSurveyForm);
Alpine.data('twoFactorSetup', twoFactorSetup);
Alpine.data('fieldSurveyForm', fieldSurveyForm);
Alpine.data('fieldSurveysIndex', fieldSurveysIndex);
registerSortable(Alpine);

Alpine.start();
