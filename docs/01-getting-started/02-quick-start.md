# Quick Start

Get exception monitoring running in your WordPress site.

## Step 1: Get API Credentials

1. Log in to [Nadi Dashboard](https://nadi.pro)
2. Create or select an application
3. Copy your **API Key** (Sanctum token) and **App Key** (application key)

## Step 2: Configure the Plugin

1. Navigate to **Settings > Nadi** in WordPress admin
2. Enter your credentials:
   - **API Key**: Your Sanctum personal access token
   - **App Key**: Your application identifier
3. Select a **Transporter**:
   - **Shipper**: Batches logs and sends via Go binary (recommended)
   - **HTTP**: Sends directly via API calls
4. Click **Save Changes**

## Step 3: Test the Connection

1. Click the **Test** button in settings
2. Check your Nadi dashboard for the test exception

## How It Works

When an exception occurs:

1. The global exception handler captures it
2. Context is collected (HTTP request, user, environment)
3. Data is logged or sent based on your transporter:
   - **Shipper**: Written to `log/` directory, sent by cron every minute
   - **HTTP**: Sent immediately to Nadi API

## Next Steps

- [Transport Methods](../03-configuration/01-transporters.md)
- [Sampling Strategies](../03-configuration/02-sampling.md)
