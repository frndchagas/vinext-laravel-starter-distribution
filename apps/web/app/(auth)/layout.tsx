import Link from "next/link";

export default function AuthLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <main className="flex min-h-dvh flex-col items-center justify-center bg-background px-4 py-12">
      <div className="w-full max-w-sm">
        <Link
          href="/"
          className="mb-8 block text-center font-[family-name:var(--font-app-display)] text-2xl text-foreground"
        >
          Vinext AI Starter
        </Link>
        <div className="rounded-xl border border-border bg-card p-6 shadow-[0_1px_0_var(--border)]">
          {children}
        </div>
      </div>
    </main>
  );
}
