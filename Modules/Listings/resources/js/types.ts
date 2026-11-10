export type ListingStatus = 'draft' | 'published' | 'suspended';

export type ServiceOption = {
    value: string;
    label: string;
};

export type ListingDetailField = {
    label: string;
    type: 'text' | 'select';
    placeholder?: string;
    options?: Record<string, string>;
};

export type ListingService = {
    key: string;
    label: string;
};

export type ListingLimits = {
    max_images: number;
    max_videos: number;
    max_image_kb: number;
    max_video_kb: number;
};

export type ListingLocations = Record<
    string,
    {
        label: string;
        municipalities: string[];
    }
>;

export type ListingMedia = {
    id: number;
    type: 'image' | 'video';
    type_label: string;
    url: string;
    mime_type: string;
    alt_text: string | null;
    sort_order: number;
    delete_url?: string;
};

export type ListingFormData = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    postal_code: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    capacity: number | null;
    services: string[];
    details: Record<string, string | null>;
    rules: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    whatsapp_phone: string | null;
    status: ListingStatus;
    status_label: string;
    published_at: string | null;
    public_url: string | null;
    media: ListingMedia[];
};

export type ListingOwnerCard = {
    id: number;
    name: string;
    slug: string;
    status: ListingStatus;
    status_label: string;
    city: string | null;
    state: string | null;
    cover_url: string | null;
    images_count: number;
    videos_count: number;
    public_url: string | null;
    manage_url: string;
    edit_url: string;
    updated_at: string | null;
};

export type ListingPublicCard = {
    id: number;
    name: string;
    slug: string;
    city: string | null;
    state: string | null;
    capacity: number | null;
    services: string[];
    cover_url: string | null;
    latitude: number | null;
    longitude: number | null;
    distance_km: number | null;
    url: string;
};

export type ListingPublicDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    postal_code: string | null;
    latitude: number | null;
    longitude: number | null;
    capacity: number | null;
    services: ListingService[];
    details: Array<{ key: string; label: string; value: string }>;
    rules: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    whatsapp_url: string | null;
    account: {
        name: string | null;
        logo_url: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        whatsapp_url: string | null;
    };
    timezone: string;
    url: string;
    media: ListingMedia[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type PublicFilters = {
    state?: string | null;
    city?: string | null;
    capacity?: string | number | null;
    service?: string | null;
    lat?: string | number | null;
    lng?: string | number | null;
    radius?: string | number | null;
};
