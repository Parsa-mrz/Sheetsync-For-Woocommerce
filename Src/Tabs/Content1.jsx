import { Input, message, Spin } from "antd";
import { useEffect, useState } from "react";

async function saveOption(field, value) {
    try {
        const res = await fetch(
            `${window.wpApiSettings.root}sheetsync/v1/update-options`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": window.wpApiSettings.nonce,
                },
                body: JSON.stringify({ [field]: value }),
            }
        );
        const data = await res.json();
        if (data.success) {
            message.success(`${field} updated successfully!`);
        } else {
            message.error(`Failed to update ${field}`);
        }
    } catch (err) {
        console.error(err);
        message.error("Error saving option");
    }
}

export default function Content1() {
    const [loading, setLoading] = useState(true);
    const [options, setOptions] = useState({
        sheet_id: "",
        sheet_name: "",
    });

    // Load saved options on mount
    useEffect(() => {
        async function fetchOptions() {
            try {
                const res = await fetch(
                    `${window.wpApiSettings.root}sheetsync/v1/get-options`,
                    {
                        headers: { "X-WP-Nonce": window.wpApiSettings.nonce },
                    }
                );
                const data = await res.json();
                if (data.success) {
                    setOptions(data.data);
                }
            } catch (err) {
                console.error("Failed to fetch options:", err);
            } finally {
                setLoading(false);
            }
        }
        fetchOptions();
    }, []);

    if (loading) return <Spin tip="Loading..." />;

    return (
        <>
            <div style={{ marginBottom: "10px" }}>
                <Input
                    name="sheet_id"
                    placeholder="Enter Google Sheet ID"
                    value={options.sheet_id}
                    onChange={(e) =>
                        setOptions((prev) => ({ ...prev, sheet_id: e.target.value }))
                    }
                    onBlur={(e) => saveOption("sheet_id", e.target.value)}
                />
            </div>
            <div>
                <Input
                    name="sheet_name"
                    placeholder="Enter Google Sheet Name"
                    value={options.sheet_name}
                    onChange={(e) =>
                        setOptions((prev) => ({ ...prev, sheet_name: e.target.value }))
                    }
                    onBlur={(e) => saveOption("sheet_name", e.target.value)}
                />
            </div>
        </>
    );
}
