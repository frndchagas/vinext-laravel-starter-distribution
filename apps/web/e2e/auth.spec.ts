import { expect, test } from "@playwright/test";

import {
  findPasswordResetLink,
  findVerificationLink,
  generateTotp,
  registerVerifiedUser,
} from "./helpers";

test("register, verify email, use the private channel, sign out and sign in again", async ({
  page,
}) => {
  test.setTimeout(120_000);

  const uniqueEmail = `e2e-${Date.now()}@example.com`;
  const password = "e2e-secret-password";

  await page.goto("/register");
  await page.getByLabel("Name").fill("E2E User");
  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByLabel("Password", { exact: true }).fill(password);
  await page.getByLabel("Confirm password").fill(password);
  await page.getByRole("button", { name: "Create account" }).click();

  await expect(page).toHaveURL(/\/verify-email$/, { timeout: 20_000 });
  await expect(page.getByText(uniqueEmail)).toBeVisible();

  const verificationLink = await findVerificationLink(uniqueEmail);
  await page.goto(verificationLink);

  await expect(page).toHaveURL(/\/dashboard\?verified=1$/, { timeout: 20_000 });
  await expect(page.getByText("Email verified")).toBeVisible();
  await expect(page.getByText("member")).toBeVisible();
  await expect(page.getByText(/Subscribed to users\./)).toBeVisible({ timeout: 15_000 });

  await page.getByRole("button", { name: "Sign out" }).click();
  await expect(page).toHaveURL(/\/login$/);

  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByLabel("Password").fill(password);
  await page.getByRole("button", { name: "Sign in" }).click();

  await expect(page).toHaveURL(/\/dashboard$/, { timeout: 20_000 });
  await expect(page.getByText(/Subscribed to users\./)).toBeVisible({ timeout: 15_000 });
});

test("rejects invalid credentials with a stable field error", async ({ page }) => {
  await page.goto("/login");
  await page.getByLabel("Email").fill("nobody@example.com");
  await page.getByLabel("Password").fill("definitely-wrong");
  await page.getByRole("button", { name: "Sign in" }).click();

  await expect(page.getByRole("alert")).toContainText(/credentials/i);
  await expect(page).toHaveURL(/\/login$/);
});

test("reset a password through the link delivered by email", async ({ page }) => {
  test.setTimeout(120_000);

  const uniqueEmail = `e2e-reset-${Date.now()}@example.com`;
  const originalPassword = "original-e2e-password";
  const newPassword = "new-e2e-password";

  await page.goto("/register");
  await page.getByLabel("Name").fill("Password Reset User");
  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByLabel("Password", { exact: true }).fill(originalPassword);
  await page.getByLabel("Confirm password").fill(originalPassword);
  await page.getByRole("button", { name: "Create account" }).click();
  await expect(page).toHaveURL(/\/verify-email$/, { timeout: 20_000 });

  await page.getByRole("button", { name: "Sign out" }).click();
  await page.goto("/forgot-password");
  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByRole("button", { name: "Send reset link" }).click();
  await expect(page.getByText(/reset link is on its way/i)).toBeVisible();

  const resetLink = await findPasswordResetLink(uniqueEmail);
  await page.goto(resetLink);
  await expect(page.getByLabel("Email")).toHaveValue(uniqueEmail);
  await page.getByLabel("New password", { exact: true }).fill(newPassword);
  await page.getByLabel("Confirm new password").fill(newPassword);
  await page.getByRole("button", { name: "Reset password" }).click();
  await expect(page).toHaveURL(/\/login$/, { timeout: 20_000 });

  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByLabel("Password").fill(newPassword);
  await page.getByRole("button", { name: "Sign in" }).click();
  await expect(page).toHaveURL(/\/verify-email$/, { timeout: 20_000 });
});

test("update account settings and complete the two-factor recovery flow", async ({ page }) => {
  test.setTimeout(120_000);

  const uniqueEmail = `e2e-settings-${Date.now()}@example.com`;
  const originalPassword = "settings-original-password";
  const newPassword = "settings-updated-password";

  await registerVerifiedUser(page, uniqueEmail, originalPassword);
  await page.getByRole("link", { name: "Settings" }).click();
  await expect(page).toHaveURL(/\/settings$/);

  const profileSection = page.locator("section").filter({
    has: page.getByRole("heading", { name: "Profile" }),
  });
  await profileSection.getByLabel("Name").fill("Updated E2E User");
  await profileSection.getByRole("button", { name: "Save profile" }).click();
  await expect(profileSection.getByText("Profile updated.")).toBeVisible();

  const passwordSection = page.locator("section").filter({
    has: page.getByRole("heading", { name: "Password" }),
  });
  await passwordSection.getByLabel("Current password").fill(originalPassword);
  await passwordSection.getByLabel("New password", { exact: true }).fill(newPassword);
  await passwordSection.getByLabel("Confirm new password").fill(newPassword);
  await passwordSection.getByRole("button", { name: "Update password" }).click();
  await expect(page).toHaveURL(/\/login\?password_updated=1$/);
  await expect(page.getByText("Password updated. Sign in again.")).toBeVisible();
  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByLabel("Password").fill(newPassword);
  await page.getByRole("button", { name: "Sign in" }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
  await page.getByRole("link", { name: "Settings" }).click();

  const twoFactorSection = page.locator("section").filter({
    has: page.getByRole("heading", { name: "Two-factor authentication" }),
  });
  await twoFactorSection.getByLabel("Current password").fill(newPassword);
  await twoFactorSection.getByRole("button", { name: "Enable two-factor authentication" }).click();

  const secret = await twoFactorSection.locator("code").textContent();
  expect(secret).toBeTruthy();
  await twoFactorSection.getByLabel("Authentication code").fill(generateTotp(secret!));
  await twoFactorSection.getByRole("button", { name: "Confirm setup" }).click();

  await expect(twoFactorSection.getByText("Active", { exact: true })).toBeVisible();
  await expect(twoFactorSection.getByRole("heading", { name: "Recovery codes" })).toBeVisible();
  const recoveryCode = await twoFactorSection.locator("li").first().textContent();
  expect(recoveryCode).toBeTruthy();

  await page.getByRole("link", { name: "Back to dashboard" }).click();
  await page.getByRole("button", { name: "Sign out" }).click();
  await page.getByLabel("Email").fill(uniqueEmail);
  await page.getByLabel("Password").fill(newPassword);
  await page.getByRole("button", { name: "Sign in" }).click();

  await expect(page).toHaveURL(/\/two-factor-challenge$/);
  await page.getByRole("button", { name: "Use a recovery code" }).click();
  await page.getByLabel("Recovery code").fill(recoveryCode!);
  await page.getByRole("button", { name: "Continue" }).click();
  await expect(page).toHaveURL(/\/dashboard$/, { timeout: 20_000 });

  await page.getByRole("link", { name: "Settings" }).click();
  const activeTwoFactorSection = page.locator("section").filter({
    has: page.getByRole("heading", { name: "Two-factor authentication" }),
  });
  await activeTwoFactorSection
    .getByRole("button", { name: "Disable two-factor authentication" })
    .click();

  const disableDialog = page.getByRole("alertdialog");
  await disableDialog.getByLabel("Current password").fill(newPassword);
  await disableDialog.getByRole("button", { name: "Disable two-factor authentication" }).click();
  await expect(activeTwoFactorSection.getByText("Disabled", { exact: true })).toBeVisible();
});
