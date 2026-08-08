import './bootstrap';

import Alpine from 'alpinejs';
import './player.js';
import './song-search.js';
import { initStoryDownload } from './story-card.js';

window.Alpine = Alpine;

Alpine.start();

initStoryDownload();
