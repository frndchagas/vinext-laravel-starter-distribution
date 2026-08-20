import { expect, test } from "@playwright/test";

import { registerVerifiedUser } from "./helpers";

test("queue a task and watch it complete live on the private channel", async ({ page }) => {
  test.setTimeout(120_000);

  await registerVerifiedUser(page, `e2e-task-${Date.now()}@example.com`, "e2e-secret-password");

  await page.goto("/tasks");
  await page.getByLabel("New task input").fill("hello brave new world");
  await page.getByRole("button", { name: "Queue task" }).click();

  const card = page.locator("article", { hasText: "hello brave new world" });
  await expect(card).toBeVisible({ timeout: 10_000 });

  await expect(card.getByText("completed")).toBeVisible({ timeout: 30_000 });
  await expect(card.getByText(/4 words/)).toBeVisible();
  await expect(card.getByText(/dlrow wen evarb olleh/)).toBeVisible();

  await page.reload();
  await expect(
    page.locator("article", { hasText: "hello brave new world" }).getByText("completed"),
  ).toBeVisible({
    timeout: 10_000,
  });
});

test("repeated submissions with distinct keys create distinct tasks", async ({ page }) => {
  test.setTimeout(120_000);

  await registerVerifiedUser(page, `e2e-task-${Date.now()}-b@example.com`, "e2e-secret-password");

  await page.goto("/tasks");

  for (let index = 0; index < 2; index += 1) {
    await page.getByLabel("New task input").fill(`double submit ${index}`);
    await page.getByRole("button", { name: "Queue task" }).click();
    await expect(page.locator("article", { hasText: `double submit ${index}` })).toBeVisible({
      timeout: 10_000,
    });
  }

  await expect(page.locator("article")).toHaveCount(2);
});

test("recover the persisted task state after the realtime connection returns", async ({
  context,
  page,
}) => {
  test.setTimeout(120_000);

  await registerVerifiedUser(
    page,
    `e2e-reconnect-${Date.now()}@example.com`,
    "e2e-secret-password",
  );

  await page.goto("/tasks");
  await page.getByLabel("New task input").fill("finish while disconnected");
  await page.getByRole("button", { name: "Queue task" }).click();

  const card = page.locator("article", { hasText: "finish while disconnected" });
  await expect(card).toBeVisible({ timeout: 10_000 });
  await context.setOffline(true);
  await page.waitForTimeout(2_500);
  await context.setOffline(false);

  await expect(card.getByText("completed")).toBeVisible({ timeout: 45_000 });
  await expect(card.getByText(/3 words/)).toBeVisible();
});
