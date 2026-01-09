from playwright.sync_api import sync_playwright

def verify_render_options():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Navigate to the localhost server
        page.goto("http://localhost:8080/index.php")

        # Sign up (needed to see the render section)
        page.fill("#signup-username", "testuser")
        page.fill("#signup-password", "password")
        page.click("#signup-btn")

        # Wait for recorder section to appear (signaling successful login)
        page.wait_for_selector("#recorder")

        # Check if Render section is visible
        page.wait_for_selector("#render")

        # Verify the "Input Type" options exist
        # Check for "Raw Text" radio button
        text_radio = page.locator("#type-text")
        if not text_radio.is_visible():
            print("Error: Raw Text radio button not visible")

        # Check for "Phonemes" radio button
        phonemes_radio = page.locator("#type-phonemes")
        if not phonemes_radio.is_visible():
            print("Error: Phonemes radio button not visible")

        # Take a screenshot of the Render section
        page.screenshot(path="verification/render_ui.png")
        print("Screenshot saved to verification/render_ui.png")

        browser.close()

if __name__ == "__main__":
    verify_render_options()
