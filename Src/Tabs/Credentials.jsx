import { useState, useEffect } from 'react';
import { Form, Input, Card, message, Spin, Upload, Button } from 'antd';
import { UploadOutlined } from '@ant-design/icons';

async function saveSpreadsheetId(value, setSaving) {
    setSaving('spreadsheetId');
    try {
        const res = await fetch(
            `${window.wpApiSettings.root}sheetsync/v1/update-options`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": window.wpApiSettings.nonce,
                },
                body: JSON.stringify({ spreadsheetId: value }),
            }
        );
        const data = await res.json();
        if (data.success) {
            message.success('Spreadsheet ID updated successfully!');
        } else {
            message.error('Failed to update Spreadsheet ID');
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

    const uploadProps = {
        name: 'jsonFile',
        action: `${window.wpApiSettings.root}sheetsync/v1/upload-credentials`,
        headers: {
            'X-WP-Nonce': window.wpApiSettings.nonce,
        },
        maxCount: 1,
        accept: '.json',
        onChange(info) {
            if (info.file.status === 'done') {
                message.success(`${info.file.name} file uploaded successfully.`);
            } else if (info.file.status === 'error') {
                message.error(`${info.file.name} file upload failed.`);
            }
        },
    };

    return (
        <Card
            title="Add Google Sheet Credentials"
            style={{ maxWidth: 600, margin: '40px auto' }}
        >
            <Form form={form} layout="vertical">
                <Form.Item
                    label="Google Service Account JSON Key"
                    name="jsonFile"
                    tooltip="Upload the JSON key file from your Google Cloud Service Account."
                >
                    <Upload {...uploadProps}>
                        <Button icon={<UploadOutlined />}>Click to Upload JSON</Button>
                    </Upload>
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
                        onBlur={(e) => saveSpreadsheetId(e.target.value, setSaving)}
                    />
                </Form.Item>
            </Form>
        </Card>
    );
}