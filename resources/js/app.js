import Alpine from 'alpinejs';
import { registerSmartRoom } from './smartroom-alpine';

registerSmartRoom(Alpine);
window.Alpine = Alpine;
Alpine.start();