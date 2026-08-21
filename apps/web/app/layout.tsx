import "@fontsource-variable/manrope";
import "@fontsource-variable/newsreader";
import type { Metadata } from "next";
import { cookies } from "next/headers";

import "./globals.css";

import { Providers } from "./providers";

export const metadata: Metadata = {
  title: "Vinext Laravel Starter",
  description:
    "A Laravel and Vinext foundation for coding agents, with typed contracts, queues and realtime.",
  openGraph: {
    description:
      "A Laravel and Vinext foundation for coding agents, with typed contracts, queues and realtime.",
    title: "Vinext Laravel Starter",
    type: "website",
  },
};

const appearanceScript = `(() => {
  try {
    const stored = localStorage.getItem("appearance");
    const appearance = ["light", "dark", "system"].includes(stored) ? stored : "system";
    const dark = appearance === "dark" || (appearance === "system" && matchMedia("(prefers-color-scheme: dark)").matches);
    document.documentElement.classList.toggle("dark", dark);
    document.documentElement.style.colorScheme = dark ? "dark" : "light";
  } catch {}
})();`;

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const appearance = (await cookies()).get("appearance")?.value;
  const explicitAppearance =
    appearance === "light" || appearance === "dark" ? appearance : undefined;

  return (
    <html
      className={explicitAppearance === "dark" ? "dark" : undefined}
      lang="en"
      style={explicitAppearance ? { colorScheme: explicitAppearance } : undefined}
      suppressHydrationWarning
    >
      <head>
        <script dangerouslySetInnerHTML={{ __html: appearanceScript }} />
      </head>
      <body>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
