import './bootstrap';

import Alpine from 'alpinejs';
import './player.js';
import './reactions.js';
import './reply-reactions.js';
import './report.js';
import './song-search.js';
import { initStoryDownload } from './story-card.js';
import { initCardShare, initDetailShare } from './share.js';
import { initFeedbackModal } from './feedback.js';
import { initInfiniteScroll } from './infinite-scroll.js';

window.Alpine = Alpine;

Alpine.start();

initStoryDownload();
initCardShare();
initDetailShare();
initFeedbackModal();
initInfiniteScroll();
