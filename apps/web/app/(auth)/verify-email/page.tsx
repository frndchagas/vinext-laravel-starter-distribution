"use client";

import {
  useGetMe,
  useLogout,
  useResendEmailVerification,
} from "@vinext-laravel-starter/api-client";
import { useRouter } from "next/navigation";
import { useEffect } from "react";

import { Button } from "@/components/ui/button";
import { problemDetail } from "@/lib/problem";

export default function VerifyEmailPage() {
  const router = useRouter();
  const meQuery = useGetMe();
  const resendMutation = useResendEmailVerification();
  const logoutMutation = useLogout();

  const me = meQuery.data?.status === 200 ? meQuery.data.data : undefined;
  const rateLimitMessage =
    resendMutation.data?.status === 429
      ? problemDetail(resendMutation.data.data, "Too many requests. Try again shortly.")
      : undefined;

  useEffect(() => {
    if (meQuery.data?.status === 401) {
      router.replace("/login");
    }

    if (me?.email_verified === true) {
      router.replace("/dashboard");
    }
  }, [meQuery.data?.status, me?.email_verified, router]);

  return (
    <div className="flex flex-col gap-4">
      <h1 className="font-[family-name:var(--font-app-display)] text-xl text-foreground">
        Verify your email
      </h1>
      <p className="text-sm text-muted-foreground">
        We sent a verification link{me ? ` to ${me.email}` : ""}. Click it to unlock the rest of the
        application.
      </p>
      {resendMutation.data?.status === 202 ? (
        <output className="block rounded-lg bg-primary/10 px-3 py-2 text-sm text-foreground">
          A new verification link is on its way.
        </output>
      ) : null}
      {rateLimitMessage ? (
        <p role="alert" className="rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive">
          {rateLimitMessage}
        </p>
      ) : null}
      <Button size="lg" disabled={resendMutation.isPending} onClick={() => resendMutation.mutate()}>
        {resendMutation.isPending ? "Sending…" : "Resend verification email"}
      </Button>
      <Button
        variant="ghost"
        disabled={logoutMutation.isPending}
        onClick={() => logoutMutation.mutate(undefined, { onSuccess: () => router.push("/login") })}
      >
        Sign out
      </Button>
    </div>
  );
}
