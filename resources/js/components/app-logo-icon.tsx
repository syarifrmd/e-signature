import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    // Prefer storage symlink path; fallback to public logo if missing
    const handleError: ImgHTMLAttributes<HTMLImageElement>["onError"] = (e) => {
        const target = e.target as HTMLImageElement;
        if (target.src.endsWith('/storage/logo/logo.png')) {
            target.src = '/logo/logo.png';
        }
    };

    return (
        <img
            src="/storage/logo/logo.png"
            alt="App Logo"
            onError={handleError}
            {...props}
        />
    );
}
