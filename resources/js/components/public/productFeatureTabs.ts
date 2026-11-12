import type { FeatureIconName } from './featureIcon';

export interface ProductFeatureItem {
    text: string;
    icon?: FeatureIconName;
}

export interface ProductFeatureTab {
    id: string;
    label: string;
    eyebrow: string;
    title: string;
    description: string;
    features: ProductFeatureItem[];
    screenshot: string | null;
    screenshotAlt: string;
}
