import "@fontsource-variable/manrope";
import "@fontsource-variable/newsreader";
import type { Metadata } from "next";

import "./globals.css";

import { Providers } from "./providers";

export const metadata: Metadata = {
  title: "Vinext AI Starter for Laravel",
  description:
    "A Laravel and Vinext foundation for coding agents, with typed contracts, queues and realtime.",
  openGraph: {
    description:
      "A Laravel and Vinext foundation for coding agents, with typed contracts, queues and realtime.",
    title: "Vinext AI Starter for Laravel",
    type: "website",
  },
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
