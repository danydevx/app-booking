export * from './auth';
export * from './navigation';
export * from './teams';
export * from './ui';

export type PageSeo = {
    title: string;
    description: string | null;
    canonical: string | null;
    image: string | null;
    robots: string;
    type: string;
    site_name: string;
    json_ld?: Record<string, unknown> | null;
};
