import Alpine from 'alpinejs';
import {
    createIcons,
    // Layout
    Home, FileText, CircleHelp, Clock, Bell, Bookmark,
    ChevronLeft, ChevronRight, ChevronUp, Menu, X, User,
    SquarePen, Settings, LogOut, LogIn, UserPlus,
    // Post card
    Ellipsis, File, Upload, ThumbsUp, MessageCircle, Send, Check,
    // Forms / pages
    ArrowLeft, AlignLeft, Image, Trash,
    Moon,
    Sun,
} from 'lucide';

const icons = {
    Home, FileText, CircleHelp, Clock, Bell, Bookmark,
    ChevronLeft, ChevronRight, ChevronUp, Menu, X, User,
    SquarePen, Settings, LogOut, LogIn, UserPlus,
    Ellipsis, File, Upload, ThumbsUp, MessageCircle, Send, Check,
    ArrowLeft, AlignLeft, Image, Trash, Sun, Moon
};

window.Alpine = Alpine;

document.addEventListener('alpine:initialized', () => {
    createIcons({ icons });
});

Alpine.start();


window.renderIcons = (root = document) => createIcons({ icons, root });
window.appendWithIcons = (container, html) => {
    const marker = container.lastElementChild;
    container.insertAdjacentHTML('beforeend', html);

    let node = marker ? marker.nextElementSibling : container.firstElementChild;
    while (node) {
        createIcons({ icons, root: node });
        node = node.nextElementSibling;
    }
};