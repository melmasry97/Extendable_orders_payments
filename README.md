# Extendable Orders & Payments API

A robust Laravel-based REST API for managing orders and payments with extensible payment gateway integration.

## Features

- **Authentication**
  - JWT-based authentication
  - User registration and login
  - Token refresh mechanism
  - Secure logout

- **Products Management**
  - CRUD operations for products
  - Stock management
  - Validation for product data
  - Protection against deletion of products with orders

- **Order System**
  - Create and manage orders
  - Add/update/remove order items
  - Order status tracking
  - Order history

- **Payment Processing**
  - Extensible payment gateway integration
  - Multiple payment gateway support
  - Payment status tracking
  - Payment history
  - Secure payment processing

- **API Features**
  - RESTful API design
  - Resource-based responses
  - Pagination support
  - Comprehensive error handling
  - Request validation
  - API versioning (V1)

## Technical Stack

- PHP 8.x
- Laravel 11.x
- JWT Authentication
- MySQL/PostgreSQL
- PHPUnit for testing

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/Extendable_orders_payments.git
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Configure your database in `.env`

5. Generate application key:
```bash
php artisan key:generate
```

6. Generate JWT secret:
```bash
php artisan jwt:secret
```

7. Run migrations:
```bash
php artisan migrate
```

## API Documentation

### Online Documentation
The complete API documentation is available online at:
[Postman Documentation](https://documenter.getpostman.com/view/20303360/2sAYQdkA6u)

This documentation includes:
- Detailed endpoint descriptions
- Request/Response examples
- Authentication details
- Error handling examples

### Authentication Endpoints

- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/refresh` - Refresh JWT token

### Product Endpoints

- `GET /api/v1/products` - List all products
- `POST /api/v1/products` - Create new product
- `GET /api/v1/products/{id}` - Get product details
- `PATCH /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product

### Order Endpoints

- `GET /api/v1/orders` - List all orders
- `POST /api/v1/orders` - Create new order
- `GET /api/v1/orders/{id}` - Get order details
- `PATCH /api/v1/orders/{id}` - Update order
- `DELETE /api/v1/orders/{id}` - Delete order

### Order Items Endpoints

- `GET /api/v1/orders/{order}/items` - List order items
- `POST /api/v1/orders/{order}/items` - Add items to order
- `GET /api/v1/orders/{order}/items/{item}` - Get item details
- `PATCH /api/v1/orders/{order}/items/{item}` - Update order item
- `DELETE /api/v1/orders/{order}/items/{item}` - Remove item from order

### Payment Endpoints

- `GET /api/v1/payments` - List all payments
- `GET /api/v1/payments/{id}` - Get payment details
- `POST /api/v1/orders/{order}/payments` - Process payment for order
- `GET /api/v1/orders/{order}/payments` - Get order payments

## Testing

Run the test suite:

```bash
php artisan test
```

## Architecture

The project follows SOLID principles and uses several design patterns:

### Patterns Used
- **Repository Pattern** for data access
- **Strategy Pattern** for payment gateways
- **Interface-based Design** for loose coupling
- **Service Layer** for business logic
- **Resource Classes** for API responses
- **Request Classes** for validation

### Project Structure
```
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Services/
├── Repositories/
├── Interfaces/
├── Exceptions/
└── Helpers/
```

## Adding New Payment Gateways

1. Create a new gateway class implementing `PaymentGatewayInterface`:
```php
class NewPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, array $data): array
    {
        // Implementation
    }

    public function validateConfig(array $config): bool
    {
        // Validation logic
    }
}
```

2. Add gateway configuration in database:
```php
PaymentGateway::create([
    'name' => 'new_gateway',
    'class_name' => NewPaymentGateway::class,
    'config' => [
        // Gateway specific config
    ],
    'is_active' => true
]);
```

## Error Handling

The API uses standardized error responses:

```json
{
    "status": "error",
    "message": "Error description",
    "errors": {
        "field": ["Error details"]
    }
}
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
