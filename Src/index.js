import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { StrictMode } from 'react';
import App from './App.jsx';

domReady(() => {
    const root = createRoot(
        document.getElementById('sheetsync-for-woocommerce-settings')
    );

    root.render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
});