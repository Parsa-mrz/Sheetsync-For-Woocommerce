import React, { useState, useEffect } from "react";
import { Spin, Card, Form, Input, Table, Typography, Row, Col } from "antd";
import Credentials from "../Tabs/Credentials";

const { Title, Paragraph } = Typography;

export default function MainDashboard() {
    const [loading, setLoading] = useState(true);
    const [settings, setSettings] = useState({});
    const [jsonData, setJsonData] = useState([]);

    useEffect(() => {
        async function fetchSettingsAndJson() {
            setLoading(true);
            try {
                const optionsRes = await fetch(
                    `${window.wpApiSettings.root}sheetsync/v1/get-options`,
                    {
                        headers: { "X-WP-Nonce": window.wpApiSettings.nonce },
                    }
                );
                const optionsData = await optionsRes.json();
                if (optionsData.success && optionsData.data) {
                    setSettings(optionsData.data);
                }

                const jsonRes = await fetch(
                    `${window.wpApiSettings.root}sheetsync/v1/get-credentials-data`,
                    {
                        headers: { "X-WP-Nonce": window.wpApiSettings.nonce },
                    }
                );
                const jsonContent = await jsonRes.json();
                if (jsonContent.success && jsonContent.data) {
                    const tableData = Object.entries(jsonContent.data).map(([key, value]) => ({
                        key,
                        field: key,
                        value: JSON.stringify(value, null, 2),
                    }));
                    setJsonData(tableData);
                }
            } catch (err) {
                console.error("Failed to fetch dashboard data:", err);
            } finally {
                setLoading(false);
            }
        }
        fetchSettingsAndJson();
    }, []);

    const columns = [
        {
            title: 'Field',
            dataIndex: 'field',
            key: 'field',
            width: '30%',
        },
        {
            title: 'Value',
            dataIndex: 'value',
            key: 'value',
            render: (text) => (
                <div style={{ whiteSpace: 'pre-wrap', wordWrap: 'break-word' }}>
                    {text}
                </div>
            ),
        },
    ];

    if (loading) {
        return <Spin tip="Loading..." style={{ display: 'block', margin: '100px auto' }} />;
    }

    return (
        <div style={{ padding: '24px' }}>
            <Title level={2}>Dashboard</Title>
            <Paragraph>Manage your Google Sheet synchronization settings and view your Service Account details.</Paragraph>

            <Row gutter={16}>
                <Col span={12}>
                    <Credentials />
                </Col>
                <Col span={12}>
                    <Card title="Service Account Details">
                        <Table
                            columns={columns}
                            dataSource={jsonData}
                            pagination={false}
                            rowKey="field"
                            size="small"
                        />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}