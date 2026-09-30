# Google OAuth Setup

1. Import `database/google_oauth.sql` into the `eksplormajaku` database once.
2. From this directory, run `composer install` to install the Google API Client Library.
3. Create an OAuth client with type **Web application** in Google Cloud Console. Add this exact authorized redirect URI:

   `http://localhost/eksplorMajaKu/Backend/api/google_callback.php`

4. Set these environment variables in the Apache virtual-host configuration. Do not put real credentials in source files or commit them.

   ```apache
   SetEnv GOOGLE_CLIENT_ID "your-client-id"
   SetEnv GOOGLE_CLIENT_SECRET "your-client-secret"
   SetEnv GOOGLE_REDIRECT_URI "http://localhost/eksplorMajaKu/Backend/api/google_callback.php"
   ```

5. Restart Apache and open `http://localhost/eksplorMajaKu/Backend/api/google_login.php` to start the sign-in flow.

The callback returns JSON and creates the same PHP session used by the email/password login. Keep the OAuth consent screen in testing mode until the app is ready for production.
