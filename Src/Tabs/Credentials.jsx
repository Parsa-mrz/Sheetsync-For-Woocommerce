import { useState, useEffect } from 'react';
import { Form, Input, Card, message, Spin } from 'antd';

async function saveOption(field, value, setSaving) {
    setSaving(field);
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
    } finally {
        setSaving(null);
    }
}

export default function Credentials() {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(null);
    const [form] = Form.useForm();

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
                if (data.success && data.data) {
                    form.setFieldsValue(data.data);
                }
            } catch (err) {
                console.error("Failed to fetch options:", err);
                message.error("Failed to load saved credentials");
            } finally {
                setLoading(false);
            }
        }
        fetchOptions();
    }, [form]);

    if (loading) return <Spin tip="Loading..." style={{ display: 'block', margin: '100px auto' }} />;

    return (
        <Card
            title="Add Google Sheet Credentials"
            style={{ maxWidth: 600, margin: '40px auto' }}
        >
            <Form form={form} layout="vertical">
                <Form.Item
                    label="Client ID"
                    name="clientId"
                    rules={[{ required: true, message: 'Please enter your Client ID' }]}
                >
                    <Input
                        placeholder="Enter Google Client ID"
                        autoComplete="off"
                        disabled={saving === "clientId"}
                        onBlur={(e) => saveOption("clientId", e.target.value, setSaving)}
                    />
                </Form.Item>

                <Form.Item
                    label="Client Secret"
                    name="clientSecret"
                    rules={[{ required: true, message: 'Please enter your Client Secret' }]}
                >
                    <Input.Password
                        placeholder="Enter Google Client Secret"
                        disabled={saving === "clientSecret"}
                        autoComplete="off"
                        onBlur={(e) => saveOption("clientSecret", e.target.value, setSaving)}
                    />
                </Form.Item>

                <Form.Item
                    label="Spreadsheet ID"
                    name="spreadsheetId"
                    rules={[{ required: true, message: 'Please enter Spreadsheet ID' }]}
                >
                    <Input
                        placeholder="Enter your Google Spreadsheet ID"
                        disabled={saving === "spreadsheetId"}
                        autoComplete="off"
                        onBlur={(e) => saveOption("spreadsheetId", e.target.value, setSaving)}
                    />
                </Form.Item>
            </Form>
        </Card>
    );
}
