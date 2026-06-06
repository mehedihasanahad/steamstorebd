# SteamStoreBD - Steam Gift Card Store

SteamStoreBD is a modern, high-performance e-commerce platform built with Laravel, specifically designed for selling Steam Gift Cards. It features a streamlined shopping experience, automated payment processing via bKash, and a robust admin dashboard.

## 🚀 Features

- **Storefront**: Clean and responsive UI for browsing gift card categories and denominations.
- **Shopping Cart**: Fully functional cart system with quantity management.
- **Automated Payments**: Integrated with **bKash Tokenized Payment** for seamless transactions.
- **Order Management**:
    - Automated order generation and tracking.
    - Guest order lookup system.
    - Authenticated user order history.
- **Security**: 
    - Rate-limiting (throttling) on critical routes (checkout, auth, lookup).
    - Social Authentication via Google (Laravel Socialite).
- **Admin Panel**: Powered by **Filament PHP**, providing a comprehensive dashboard to manage:
    - Products (Gift Cards) and Categories.
    - Inventory (Gift Card Codes).
    - Orders and Payments.
    - Customer Reviews.
    - Site Settings.
- **SEO Optimized**: Built-in sitemap generation and robots.txt configuration.
- **Developer Friendly**: Custom scripts for easy setup and local development.

## 🛠️ Tech Stack

- **Framework**: [Laravel 12.x](https://laravel.com)
- **Admin Panel**: [Filament v3](https://filamentphp.com)
- **Payment Gateway**: [bKash (Tokenized)](https://github.com/karim007/laravel-bkash-tokenize)
- **Authentication**: Laravel Breeze + Laravel Socialite (Google)
- **Frontend**: Tailwind CSS + Vite
- **Database**: MySQL / PostgreSQL / SQLite

## 📥 Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM

### Setup Steps

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd steamstorebd
   ```

2. **Initialize the project**:
   This project includes a convenient setup script:
   ```bash
   composer setup
   ```
   *This command installs dependencies, copies `.env`, generates the application key, runs migrations, and builds frontend assets.*

3. **Configure Environment**:
   Edit the `.env` file to set your database credentials and bKash API keys:
   ```env
   DB_CONNECTION=mysql
   DB_DATABASE=steamstorebd
   
   BKASH_APP_KEY=your_app_key
   BKASH_APP_SECRET=***   # ... other bKash config
   
   GOOGLE_CLIENT_ID=your_google_id
   GOOGLE_CLIENT_SECRET=your_g...et
   ```

## 💻 Development

To start the development server, queue listener, and Vite watcher concurrently, use:

```bash
composer dev
```

## 🧪 Testing

The project uses [Pest](https://pestphp.com) for testing. To run the test suite:

```bash
composer test
```

## 📁 Key Project Structure

- `app/Models/`: Business logic and database schemas (GiftCard, Order, BkashPayment, etc.)
- `app/Http/Controllers/`: Request handling for Storefront, Checkout, and Payments.
- `app/Providers/Filament/`: Admin panel configuration.
- `routes/web.php`: Public and authenticated routes.
- `resources/views/`: Blade templates for the storefront.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
