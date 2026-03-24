import { test, expect } from '@playwright/test';

test.describe('Clawra Site', () => {
    test('should load coordinator page', async ({ page }) => {
        await page.goto('http://localhost:8001');

        // Check page title
        await expect(await page.title()).toContain('Clawra Coordinator');

        // Check for coordinator header
        const h1 = await page.locator('h1').first();
        await expect(h1).toContainText('Clawra Phase 0 Control Room');

        // Check for chat messages container
        const chatMessages = await page.locator('#chat-messages');
        await expect(chatMessages).toBeVisible();

        // Check for input field
        const userInput = await page.locator('#user-input');
        await expect(userInput).toBeVisible();
    });

    test('should have coordinator chat interface', async ({ page }) => {
        await page.goto('http://localhost:8001');

        // Check if coordinator chat UI is present
        const body = await page.locator('body');
        const htmlContent = await body.innerHTML();

        expect(htmlContent).toContain('Coordinator Chat');

        // Check for send button
        const sendButton = await page.locator('#send-button');
        await expect(sendButton).toBeVisible();
        await expect(sendButton).toHaveText(/send|Send/i);
    });

    test('should show welcome message in chat', async ({ page }) => {
        await page.goto('http://localhost:8001');

        // Look for the welcome message
        const chatMessagesDiv = await page.locator('#chat-messages');
        const messagesContent = await chatMessagesDiv.textContent();

        expect(messagesContent).toContain('Hello');
    });
});