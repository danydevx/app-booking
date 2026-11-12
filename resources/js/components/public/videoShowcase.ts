export interface VideoShowcaseTrack {
    src: string;
    label: string;
    lang: string;
    kind?: 'subtitles' | 'captions';
    default?: boolean;
}

export interface VideoShowcaseMedia {
    src: string | null;
    poster: string | null;
    tracks: VideoShowcaseTrack[];
}
