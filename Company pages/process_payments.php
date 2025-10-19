<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect billing information
    $fullname = htmlspecialchars($_POST['fullname']);
    $email = htmlspecialchars($_POST['email']);
    $address = htmlspecialchars($_POST['address']);
    $city = htmlspecialchars($_POST['city']);
    $country = htmlspecialchars($_POST['country']);
    $zip = htmlspecialchars($_POST['zip']);
    $paymentMethod = htmlspecialchars($_POST['payment']);

    // Order summary
    $subtotal = 199.00;
    $tax = 19.90;
    $total = 218.90;

    // Save order info locally (optional)
    $orderData = "
    Customer: $fullname
    Email: $email
    Address: $address, $city, $country - $zip
    Payment Method: $paymentMethod
    Total: $$total
    ------------------------
    ";

    file_put_contents("orders.txt", $orderData, FILE_APPEND);

    // ✅ Redirect based on payment method
    if ($paymentMethod === "paypal") {
        // PayPal payment URL (sandbox example)
        $paypalURL = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        $paypalID = "your-paypal-business-email@example.com"; // <-- Replace this with your PayPal email

        echo "
        <form id='paypalForm' action='$paypalURL' method='post'>
            <input type='hidden' name='business' value='$paypalID'>
            <input type='hidden' name='cmd' value='_xclick'>
            <input type='hidden' name='item_name' value='Language Services Order'>
            <input type='hidden' name='amount' value='$total'>
            <input type='hidden' name='currency_code' value='USD'>
            <input type='hidden' name='return' value='http://localhost/success.html'>
            <input type='hidden' name='cancel_return' value='http://localhost/cancel.html'>
        </form>
        <script>document.getElementById('paypalForm').submit();</script>
        ";
    } else {
        // For Card payments, show a success message (you can later link to Stripe/Flutterwave)
        echo "
        <h2>Payment Successful!</h2>
        <p>Thank you, $fullname. Your payment of $$total has been received.</p>
        ";
    }
}
?>
