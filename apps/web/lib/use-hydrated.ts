"use client";

import { useSyncExternalStore } from "react";

const emptySubscribe = () => () => {};

/**
 * False during SSR and the pre-hydration window, true afterwards. Submit
 * buttons stay disabled until hydration so a native form submission can
 * never leak field values into the URL.
 */
export function useHydrated(): boolean {
  return useSyncExternalStore(
    emptySubscribe,
    () => true,
    () => false,
  );
}
