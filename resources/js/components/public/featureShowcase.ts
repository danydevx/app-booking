import type { FeatureIconName } from './featureIcon';

export interface FeatureSlide {
    id: string;
    eyebrow?: string;
    title: string;
    description?: string;
    features: string[];
    icon?: FeatureIconName;
    image?: string | null;
    imageAlt?: string;
}
