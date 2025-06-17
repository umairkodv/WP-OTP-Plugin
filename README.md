# Email OTP Verification Plugin

This plugin replaces the email link verification feature of the **Theme My Login** plugin with email-based OTP (One-Time Password) verification, enhancing security and ensuring a seamless user experience during the login process.

## Features

- **Email-based OTP Verification**: Replaces the default email link verification with a time-sensitive OTP.
- **Customizable Expiry Time**: Configurable OTP expiry time (e.g., 5 minutes).
- **Easy Integration**: Plug-and-play with the **Theme My Login** plugin.
- **Secure**: Ensures that users only proceed with valid, time-sensitive OTPs.
- **Lightweight**: Minimal overhead on your WordPress website.
- **Custom OTP Verification Page**: Easily create a custom page for OTP input using a shortcode.

## Installation

1. **Download the Plugin**  
   Download the `email-otp-verification` plugin from the GitHub repository or use the ZIP file from the releases section.

2. **Upload to WordPress**  
   - Go to the **WordPress Admin Panel**.
   - Navigate to **Plugins** → **Add New**.
   - Click **Upload Plugin**, choose the `.zip` file, and click **Install Now**.

3. **Activate the Plugin**  
   Once installed, click **Activate** to enable the plugin.

## Usage

### Creating a Custom OTP Verification Page

1. **Create a New Page in WordPress**
   - Go to the **WordPress Admin Panel**.
   - Navigate to **Pages** → **Add New**.
   - Title the page (e.g., "OTP Verification").

2. **Add the Shortcode**
   Use the shortcode `[otp_verification_form]` within the page content to display the OTP verification form. This shortcode will render the form where users can input the OTP they received in their email.

   Example:
   ```plaintext
   Please enter the OTP sent to your email address to complete your login:
   [otp_verification_form]
