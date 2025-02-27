# Product Recommendations for WooCommerce

A WordPress plugin that allows team members to create and manage product recommendations for their customers.

## Description

Product Recommendations is a WooCommerce extension that enables team members to create personalized product recommendations for their customers. This plugin adds a new section to the WooCommerce My Account area where team members can manage their customers and create product recommendations.

## Features

- **Customer Management**: Add and manage customers from WordPress user database
- **Product Recommendations**: Search and recommend WooCommerce products to customers
- **Status Tracking**: Track the status of recommendations (pending, active, completed)
- **Notes**: Add personalized notes to each recommendation
- **User-friendly Interface**: Clean, intuitive interface using Bulma CSS framework

## Installation

1. Upload the `product-recommendations` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated

## Usage

### Managing Customers

1. Navigate to My Account > Product Recommendations
2. Click "Add Customer" to search for and add existing WordPress users as customers
3. View your customer list and manage recommendations for each customer

### Creating Recommendations

1. From your customer list, click "Manage Recommendations" for a specific customer
2. Search for products using the product search field
3. Select a product and add optional notes
4. Click "Add Recommendation" to add the product to the customer's recommendations

### Recommendation Statuses

Each recommendation has a status that helps track its progress:

- **Pending** (Yellow): The default status when a recommendation is first created. Indicates that the recommendation has been made but the customer hasn't acted on it yet.
- **Active** (Green): Can be used when a customer has viewed or acknowledged the recommendation.
- **Completed** (Red): Indicates that the customer has purchased the recommended product or otherwise completed the recommendation cycle.

## Database Structure

The plugin creates two custom database tables:

1. **pr_customers**: Stores the relationship between team members and customers
   - id (primary key)
   - user_id (WordPress user ID of the customer)
   - team_member_id (WordPress user ID of the team member)
   - date_added
   - status (active/inactive)

2. **pr_recommendations**: Stores product recommendations
   - id (primary key)
   - customer_id (foreign key to pr_customers)
   - team_member_id (WordPress user ID of the team member)
   - product_id (WooCommerce product ID)
   - date_created
   - status (pending/active/completed)
   - notes

## Relationships

- A team member can have multiple customers
- A customer can be associated with multiple team members
- A team member can create multiple product recommendations for each customer
- A customer can have multiple product recommendations from different team members

## Customization

The plugin uses Bulma CSS framework for styling. You can customize the appearance by modifying the CSS in the `public/css/product-recommendations-public.css` file.

## Support

For support, please contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

