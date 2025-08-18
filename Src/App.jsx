import SettingForm from "./Components/SettingForm";
import Layout from "./Components/Layout";
import { message } from 'antd';

message.config({
    top: 50,
    duration: 2,
    maxCount: 3,
});
export default function App() {
    return (
        <>
            <Layout />
        </>
    );
}