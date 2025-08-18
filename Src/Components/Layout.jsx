export default function Layout(props) {
    return (
        <div className="p-6 bg-white rounded-lg shadow-md">
            {props.children}
        </div>
    );
}