import './bootstrap';

import Alpine from 'alpinejs';
import './player.js';
import './reactions.js';
import './song-search.js';
import { initStoryDownload } from './story-card.js';
import { initCardShare, initDetailShare } from './share.js';

window.Alpine = Alpine;

Alpine.start();

initStoryDownload();
initCardShare();
initDetailShare();
