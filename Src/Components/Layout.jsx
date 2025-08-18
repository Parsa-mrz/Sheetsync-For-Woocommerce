import { useState, useEffect } from "react";
import { Spin } from "antd";
import SettingForm from "./SettingForm";
import MainDashboard from "./MainDashboard";

export default function Layout() {
    const [isSetupComplete, setIsSetupComplete] = useState(false);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function fetchSetupStatus() {
            try {
                const res = await fetch(
                    `${window.wpApiSettings.root}sheetsync/v1/get-options`,
                    {
                        headers: { "X-WP-Nonce": window.wpApiSettings.nonce },
                    }
                );
                const data = await res.json();
                if (data.success && data.data && data.data.setupComplete) {
                    setIsSetupComplete(true);
                }
            } catch (err) {
                console.error("Failed to fetch setup status:", err);
            } finally {
                setLoading(false);
            }
        }
        fetchSetupStatus();
    }, []);

    if (loading) return <Spin tip="Loading..." style={{ display: 'block', margin: '100px auto' }} />;

    return (
        <>
            <h1>
                SheetSync For WooCommerce
            </h1>
            <div className="mt-10 p-10 bg-white rounded-lg shadow-md">
                {isSetupComplete ? <MainDashboard /> : <SettingForm onSetupComplete={() => setIsSetupComplete(true)} />}
            </div>
        </>
    );
}