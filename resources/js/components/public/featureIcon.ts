import {
    ArrowRight,
    BadgeCheck,
    CalendarDays,
    ChartColumn,
    Check,
    CircleQuestionMark,
    ClipboardList,
    FileText,
    Globe,
    Handshake,
    HeartHandshake,
    Images,
    Inbox,
    Kanban,
    LayoutDashboard,
    ListTodo,
    Mail,
    MapPin,
    MessageSquare,
    Minus,
    Rocket,
    Send,
    Signature,
    Sparkles,
    Store,
    TrendingUp,
    Users,
} from '@lucide/vue';

/**
 * Public icon registry. Keep names kebab-cased so pages can use them as
 * strings without importing individual Lucide components.
 */
export const featureIcons = {
    'arrow-right': ArrowRight,
    'badge-check': BadgeCheck,
    'calendar-days': CalendarDays,
    'chart-column': ChartColumn,
    check: Check,
    'circle-question-mark': CircleQuestionMark,
    'clipboard-list': ClipboardList,
    'file-text': FileText,
    globe: Globe,
    handshake: Handshake,
    'heart-handshake': HeartHandshake,
    images: Images,
    inbox: Inbox,
    kanban: Kanban,
    'layout-dashboard': LayoutDashboard,
    'list-todo': ListTodo,
    mail: Mail,
    'map-pin': MapPin,
    'message-square': MessageSquare,
    minus: Minus,
    rocket: Rocket,
    send: Send,
    signature: Signature,
    sparkles: Sparkles,
    store: Store,
    'trending-up': TrendingUp,
    users: Users,
} as const;

export type FeatureIconName = keyof typeof featureIcons;

export type FeatureIconSize = 'sm' | 'md' | 'lg';

export type FeatureIconVariant = 'primary' | 'neutral' | 'plain' | 'muted';
