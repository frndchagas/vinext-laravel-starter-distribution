"use client";

import { useForgotPassword } from "@vinext-ai-starter/api-client";
import Link from "next/link";

import { Button } from "@/components/ui/button";
import { Field } from "@/components/ui/field";
import { formValue } from "@/lib/form";
import { validationErrors } from "@/lib/problem";
import { useHydrated } from "@/lib/use-hydrated";

export default function ForgotPasswordPage() {
  const hydrated = useHydrated();
  const forgotMutation = useForgotPassword();

  const errors =
    forgotMutation.data?.status === 422 ? validationErrors(forgotMutation.data.data) : {};
  const sent = forgotMutation.data?.status === 200;

  function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = new FormData(event.currentTarget);

    forgotMutation.mutate({ data: { email: formValue(form, "email") } });
  }

  return (
    <form onSubmit={submit} className="flex flex-col gap-4">
      <h1 className="font-[family-name:var(--font-app-display)] text-xl text-foreground">
        Reset password
      </h1>
      {sent ? (
        <output className="block rounded-lg bg-primary/10 px-3 py-2 text-sm text-foreground">
          If the address exists, a reset link is on its way.
        </output>
      ) : null}
      <Field
        label="Email"
        name="email"
        type="email"
        autoComplete="email"
        required
        errors={errors["email"]}
      />
      <Button type="submit" size="lg" disabled={!hydrated || forgotMutation.isPending}>
        {forgotMutation.isPending ? "Sending…" : "Send reset link"}
      </Button>
      <p className="text-center text-sm text-muted-foreground">
        Remembered it?{" "}
        <Link href="/login" className="text-primary hover:underline">
          Sign in
        </Link>
      </p>
    </form>
  );
}
