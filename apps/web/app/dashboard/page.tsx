"use client";

import { useQueryClient } from "@tanstack/react-query";
import { getGetMeQueryKey, useGetMe, useLogout } from "@vinext-ai-starter/api-client";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { disconnectEcho, getEcho } from "@/lib/echo";

type RealtimeStatus = "connecting" | "connected" | "unavailable";

export default function DashboardPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const meQuery = useGetMe();
  const logoutMutation = useLogout();
  const [realtime, setRealtime] = useState<RealtimeStatus>("connecting");

  const me = meQuery.data?.status === 200 ? meQuery.data.data : undefined;

  useEffect(() => {
    if (meQuery.data?.status === 401) {
      router.replace("/login");
    }

    if (me !== undefined && !me.email_verified) {
      router.replace("/verify-email");
    }
  }, [meQuery.data?.status, me, router]);

  useEffect(() => {
    if (me === undefined || !me.email_verified) {
      return undefined;
    }

    const echo = getEcho();
    const channel = echo.private(`users.${me.id}`);

    channel.subscribed(() => setRealtime("connected"));
    channel.error(() => setRealtime("unavailable"));

    return () => {
      echo.leave(`users.${me.id}`);
      disconnectEcho();
    };
  }, [me]);

  function signOut() {
    logoutMutation.mutate(undefined, {
      onSuccess: () => {
        disconnectEcho();
        queryClient.removeQueries({ queryKey: getGetMeQueryKey() });
        router.push("/login");
      },
    });
  }

  if (me === undefined) {
    return (
      <main className="flex min-h-dvh items-center justify-center bg-background">
        <p className="text-sm text-muted-foreground">Loading session…</p>
      </main>
    );
  }

  return (
    <main className="mx-auto flex min-h-dvh w-full max-w-3xl flex-col gap-8 bg-background px-6 py-12">
      <header className="flex items-start justify-between gap-4 border-b border-border pb-6">
        <div>
          <p className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
            Dashboard
          </p>
          <h1 className="mt-1 font-[family-name:var(--font-app-display)] text-3xl text-foreground">
            {me.name}
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">{me.email}</p>
        </div>
        <div className="flex items-center gap-4">
          <Link href="/tasks" className="text-sm text-primary hover:underline">
            Tasks
          </Link>
          <Link href="/settings" className="text-sm text-primary hover:underline">
            Settings
          </Link>
          <Button variant="outline" disabled={logoutMutation.isPending} onClick={signOut}>
            Sign out
          </Button>
        </div>
      </header>

      <section className="grid gap-6 sm:grid-cols-2">
        <div className="rounded-xl border border-border bg-card p-5">
          <h2 className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
            Session
          </h2>
          <dl className="mt-3 flex flex-col gap-2 text-sm">
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">User ID</dt>
              <dd className="truncate font-mono text-xs leading-5">{me.id}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Email verified</dt>
              <dd>{me.email_verified ? "Yes" : "No"}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Roles</dt>
              <dd>{me.roles.join(", ") || "none"}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Permissions</dt>
              <dd className="text-right">{me.permissions.join(", ") || "none"}</dd>
            </div>
          </dl>
        </div>

        <div className="rounded-xl border border-border bg-card p-5">
          <h2 className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
            Realtime
          </h2>
          <p className="mt-3 flex items-center gap-2 text-sm">
            <span
              aria-hidden
              className={
                realtime === "connected"
                  ? "size-2 rounded-full bg-primary"
                  : "size-2 rounded-full bg-muted-foreground/40"
              }
            />
            {realtime === "connected"
              ? `Subscribed to users.${me.id}`
              : realtime === "unavailable"
                ? "Realtime unavailable"
                : "Connecting…"}
          </p>
          <p className="mt-2 text-xs text-muted-foreground">
            Private channel authorized through the same Laravel session.
          </p>
        </div>
      </section>
    </main>
  );
}
