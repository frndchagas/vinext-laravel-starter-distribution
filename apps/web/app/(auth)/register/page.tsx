"use client";

import { useQueryClient } from "@tanstack/react-query";
import { getGetMeQueryKey, useRegister } from "@vinext-ai-starter/api-client";
import Link from "next/link";
import { useRouter } from "next/navigation";

import { Button } from "@/components/ui/button";
import { Field } from "@/components/ui/field";
import { formValue } from "@/lib/form";
import { problemDetail, validationErrors } from "@/lib/problem";
import { useHydrated } from "@/lib/use-hydrated";

export default function RegisterPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const hydrated = useHydrated();
  const registerMutation = useRegister();

  const errors =
    registerMutation.data?.status === 422 ? validationErrors(registerMutation.data.data) : {};
  const disabledMessage =
    registerMutation.data?.status === 403
      ? problemDetail(registerMutation.data.data, "Registration is disabled.")
      : undefined;

  function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = new FormData(event.currentTarget);

    registerMutation.mutate(
      {
        data: {
          name: formValue(form, "name"),
          email: formValue(form, "email"),
          password: formValue(form, "password"),
          password_confirmation: formValue(form, "password_confirmation"),
        },
      },
      {
        onSuccess: (response) => {
          if (response.status === 201) {
            queryClient.removeQueries({ queryKey: getGetMeQueryKey() });
            router.push("/verify-email");
          }
        },
      },
    );
  }

  return (
    <form onSubmit={submit} className="flex flex-col gap-4">
      <h1 className="font-[family-name:var(--font-app-display)] text-xl text-foreground">
        Create account
      </h1>
      {disabledMessage ? (
        <p role="alert" className="rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive">
          {disabledMessage}
        </p>
      ) : null}
      <Field label="Name" name="name" autoComplete="name" required errors={errors["name"]} />
      <Field
        label="Email"
        name="email"
        type="email"
        autoComplete="email"
        required
        errors={errors["email"]}
      />
      <Field
        label="Password"
        name="password"
        type="password"
        autoComplete="new-password"
        required
        errors={errors["password"]}
      />
      <Field
        label="Confirm password"
        name="password_confirmation"
        type="password"
        autoComplete="new-password"
        required
        errors={errors["password_confirmation"]}
      />
      <Button type="submit" size="lg" disabled={!hydrated || registerMutation.isPending}>
        {registerMutation.isPending ? "Creating account…" : "Create account"}
      </Button>
      <p className="text-center text-sm text-muted-foreground">
        Already registered?{" "}
        <Link href="/login" className="text-primary hover:underline">
          Sign in
        </Link>
      </p>
    </form>
  );
}
