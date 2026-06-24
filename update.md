Store name is 'ANJIANA'

Tech Stack:
- Frontend: HTML, CSS, JavaScript (Vanilla JS)
- Backend: Firebase
- Database: Firestore
- Image Storage: Firebase Storage
- Authentication: Firebase Authentication
- Hosting: Firebase Hosting

I want to convert this static website into a fully dynamic e-commerce application.

Requirements:

1. Admin Authentication
- Create a secure admin login page.
- Only authenticated admins can access the dashboard.
- Customers should never access admin pages.

2. Admin Dashboard
Create a responsive dashboard with:
- Dashboard overview
- Products
- Orders
- Customers
- Logout

3. Product Management
Admin should be able to:
- Add new products
- Edit existing products
- Delete products
- Change prices
- Update stock quantity
- Update descriptions
- Upload multiple images

Product fields:
- Product Name
- Category
- Price
- Discount Price (optional)
- Description
- Stock
- Sizes
- Colors
- Image URLs
- Created At

Store all product data in Firestore and images in Firebase Storage.

4. Dynamic Product Loading

Do NOT hardcode products in HTML.

Every product page should load products from Firestore dynamically.

When a new product is added from the admin panel, it should automatically appear on the website without redeploying.

5. Shopping Cart

Implement:
- Add to Cart
- Remove from Cart
- Update Quantity
- Cart Total
- Local Storage persistence

6. Checkout

Create a checkout page that collects:
- Customer Name
- Phone Number
- Email
- Address
- District
- Postal Code

Save orders in Firestore.

Prepare the code so a PayHere payment gateway can be integrated later.

7. Order Management

Admin dashboard should display:

Order Number
Customer
Products
Amount
Payment Status
Order Status

Statuses:
- Pending
- Processing
- Shipped
- Delivered
- Cancelled

Admin should be able to update order status.

8. Firebase Structure

Use:

Firestore

products/
orders/
users/
categories/

Firebase Storage

products/
banners/
avatars/

9. Security

Use Firebase Authentication and Firestore Security Rules so:

Customers:
- Read products
- Create orders

Admins:
- Read/write/update/delete products
- Read/update orders

10. SEO

Generate:
- sitemap.xml
- robots.txt
- Meta titles
- Meta descriptions
- Open Graph tags
- Product structured data (JSON-LD)

11. Performance

Optimize:
- Lazy loading images
- Image compression
- Firebase CDN caching
- Responsive design
- Mobile-first layout

12. Hosting

Configure the project for Firebase Hosting.

Use:
firebase init hosting
firebase deploy

Prepare the project so a custom domain (redikade.com or redikade.lk) can be connected with automatic SSL certificates.

13. Code Structure

Organize the project like:

/
index.html
products.html
product.html
cart.html
checkout.html

/css
/js
/assets

/admin
login.html
dashboard.html
products.html
orders.html

/firebase
firebase-config.js

Make the code modular, clean, reusable, and production-ready.

14. Cost Optimization

Design the application to stay within Firebase free limits as much as possible:
- Minimize Firestore reads
- Cache product data
- Optimize Storage usage
- Reduce unnecessary network requests

Generate complete HTML, CSS, JavaScript, and Firebase code with comments and explain every file that is created.