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

## FAQ

**Q1: Does this plugin work without Theme My Login?**  
A1: This plugin is designed to work with **Theme My Login**. If you’re not using Theme My Login, the plugin will not function as expected. However, you can customize the OTP functionality for other plugins or login flows.

**Q2: Can I adjust the OTP expiry time?**  
A2: Yes! You can adjust the expiry time of the OTP from the plugin settings or by using the `otp_verification_expiry` filter.

**Q3: How do I reset the OTP?**  
A3: If the OTP expires or is invalid, users will need to request a new OTP via the login page.

**Q4: Can I customize the OTP verification form?**  
A4: Yes! You can customize the OTP verification form layout and styling by modifying the shortcode output or the plugin's form HTML.

**Q5: How can I handle failed OTP attempts?**  
A5: You can use the `otp_verification_failed` hook to customize the action when a user submits an invalid OTP. For example, you can log failed attempts or restrict further submissions for a specific time period.


## Changelog

### Version 1.0
- Initial release of the plugin.
- Replaces the default Theme My Login email link verification with OTP verification.
- Added support for creating a custom OTP verification page using a shortcode.
- Allows customization of OTP expiry time.
- Provides example code for integrating OTP verification into your WordPress site.

### Version 1.1 *(Planned)*
- Bug fixes and improvements (if applicable).
- Added a settings page to allow users to configure OTP expiration time.



## License

This plugin is licensed under the **MIT License**.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is provided to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT, OR OTHERWISE, ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


## Author

- **Umair Zubair**
- GitHub: [@umairzubair](https://github.com/umairzubair)
