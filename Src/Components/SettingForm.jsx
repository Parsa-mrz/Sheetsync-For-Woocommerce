import React, { useState } from 'react';
import { Button, message, Steps, theme } from 'antd';
import Welcome from "../Tabs/Welcome";
import Credentials from "../Tabs/Credentials";

const steps = [
    {
        title: 'Welcome',
        content: <Welcome />
    },
    {
        title: 'Credentials',
        content: <Credentials />
    },
];

async function completeWizard(onSetupComplete) {
    try {
        const res = await fetch(
            `${window.wpApiSettings.root}sheetsync/v1/update-options`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": window.wpApiSettings.nonce,
                },
                body: JSON.stringify({ setup_complete: true }),
            }
        );
        const data = await res.json();
        if (data.success) {
            message.success('Setup complete!');
            onSetupComplete();
        } else {
            message.error('Failed to save setup status.');
        }
    } catch (error) {
        console.error('API Error:', error);
        message.error('An error occurred during API call.');
    }
}

export default function SettingForm({ onSetupComplete }) {
    const { token } = theme.useToken();
    const [current, setCurrent] = useState(0);
    const next = () => {
        setCurrent(current + 1);
    };
    const prev = () => {
        setCurrent(current - 1);
    };
    const items = steps.map(item => ({ key: item.title, title: item.title }));
    const contentStyle = {
        lineHeight: '260px',
        textAlign: 'center',
        color: token.colorTextTertiary,
        backgroundColor: token.colorFillAlter,
        borderRadius: token.borderRadiusLG,
        border: `1px dashed ${token.colorBorder}`,
        marginTop: 16,
    };

    return (
        <>
            <Steps current={current} items={items} />
            <div style={contentStyle}>{steps[current].content}</div>
            <div style={{ marginTop: 24 }}>
                {current < steps.length - 1 && (
                    <Button type="primary" onClick={() => next()}>
                        Next
                    </Button>
                )}
                {current === steps.length - 1 && (
                    <Button type="primary" onClick={() => completeWizard(onSetupComplete)}>
                        Done
                    </Button>
                )}
                {current > 0 && (
                    <Button style={{ margin: '0 8px' }} onClick={() => prev()}>
                        Previous
                    </Button>
                )}
            </div>
        </>
    );
}