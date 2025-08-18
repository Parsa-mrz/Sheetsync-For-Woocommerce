export default function Layout(props) {
    return (
        <>
            <h1>
                SheetSync For WooCommerce
            </h1>
            <div className="mt-10 p-10 bg-white rounded-lg shadow-md">
                {props.children}
            </div>
        </>
    );
}