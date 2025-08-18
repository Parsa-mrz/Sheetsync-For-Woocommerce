import { Card, Row, Col, Typography, Button } from 'antd';

const { Title, Paragraph } = Typography;

export default function Welcome() {
    return (
        <div style={{ padding: 32 }}>
            <Card bordered={false} style={{ maxWidth: 900, margin: '0 auto' }}>
                <Title level={2}>Welcome to SheetSync!</Title>
                <Paragraph>
                    Your ultimate plugin to sync WooCommerce products with Google Sheets seamlessly.
                </Paragraph>

                <Row gutter={[16, 16]} style={{ marginTop: 24 }}>
                    <Col xs={24} sm={12}>
                        <Card  title="Getting Started">
                            <Paragraph>
                                Set up your Google Sheet API credentials and start syncing products in seconds.
                            </Paragraph>
                        </Card>
                    </Col>

                    <Col xs={24} sm={12}>
                        <Card  title="Sync Options">
                            <Paragraph>
                                Choose which products, categories, or fields you want to sync automatically.
                            </Paragraph>
                        </Card>
                    </Col>

                    <Col xs={24} sm={12}>
                        <Card  title="Manage Sheets">
                            <Paragraph>
                                View and manage all your synced sheets in one place.
                            </Paragraph>
                        </Card>
                    </Col>

                    <Col xs={24} sm={12}>
                        <Card  title="Help & Support">
                            <Paragraph>
                                Access tutorials, FAQs, and contact our support if you need help.
                            </Paragraph>
                        </Card>
                    </Col>
                </Row>
            </Card>
        </div>
    );
}
