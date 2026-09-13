import templateData from './landingTemplate.json';

export interface LandingTemplateSeed {
    subtitle: string;
    ctaText: string;
    whatsappUrl: string;
}

type LandingTemplate = {
    html: string;
    css: string;
};

const escapeHtml = (value: string): string =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

export const buildLandingTemplate = ({ subtitle, ctaText, whatsappUrl }: LandingTemplateSeed): LandingTemplate => ({
    html: templateData.html
        .replace(/\[\[subtitle\]\]/g, escapeHtml(subtitle))
        .replace(/\[\[ctaText\]\]/g, escapeHtml(ctaText))
        .replace(/\[\[whatsappUrl\]\]/g, whatsappUrl),
    css: templateData.css,
});