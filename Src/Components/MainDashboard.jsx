import React, { useState, useEffect } from "react";
import { Spin, Card, Form, Input, Table, Typography, Row, Col, Button, message, Flex } from "antd";
import Credentials from "../Tabs/Credentials";

const { Title, Paragraph } = Typography;

async function resetSheetHeadline() {
    try {
        const res = await fetch(
            `${window.wpApiSettings.root}sheetsync/v1/update-options`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": window.wpApiSettings.nonce,
                },
                body: JSON.stringify({ initial_setup_done: false }),

            }
        );
        const data = await res.json();
        if (data.success) {
            message.success('Headline has reset');
        } else {
            message.error('Failed to save setup status.');
        }
    } catch (error) {
        console.error('API Error:', error);
        message.error('An error occurred during API call.');
    }
}

async function copySheetConfig() {
    const scriptContent = `function onEdit(e) {
  // Get the edited sheet and the range
  var sheet = e.range.getSheet();
  var range = e.range;

  // Make sure you're on the correct sheet (e.g., 'Sheet1')
  if (sheet.getName() !== 'Sheet1') {
    return;
  }

  // The row that was edited
  var row = range.getRow();

  // We only care about edits to existing product rows (not the header)
  if (row <= 1) {
    return;
  }

  // Get the product ID from the first column (Column A) of the edited row
  var productID = sheet.getRange(row, 1).getValue();

  if (!productID) {
    Logger.log('No Product ID found in Column A. Aborting.');
    return;
  }

  // Get all values from the edited row to send to the webhook
  var values = sheet.getRange(row, 1, 1, sheet.getLastColumn()).getValues()[0];

  // Define the webhook URL for your WordPress site
  var webhookUrl = 'https://your-website.com/wp-json/sheetsync/v1/sync-from-sheet'; // REPLACE THIS

  var data = {
    'product_id': productID,
    'product_data': values
  };

  var options = {
    'method': 'post',
    'contentType': 'application/json',
    'payload': JSON.stringify(data)
  };

  try {
    UrlFetchApp.fetch(webhookUrl, options);
    Logger.log('Webhook sent successfully for Product ID: ' + productID);
  } catch (err) {
    Logger.log('Webhook failed: ' + err.message);
  }
}`;

    try {
        await navigator.clipboard.writeText(scriptContent);
        message.success('Google Apps Script copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy text: ', err);
        message.error('Failed to copy to clipboard.');
    }
}

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
                    <Card
                        title="General Tools"
                        style={{ maxWidth: 600, margin: '40px auto' }}
                    >
                        <Flex gap="middle">
                            <Button onClick={() => { resetSheetHeadline() }}>
                                Reset Google Sheet Headeline
                            </Button>
                            <Button onClick={() => { copySheetConfig() }}>
                                Copy Google Sheet Config
                            </Button>
                        </Flex>
                    </Card>
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