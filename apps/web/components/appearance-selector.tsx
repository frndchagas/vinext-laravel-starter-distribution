"use client";

import { Monitor, Moon, Sun } from "lucide-react";

import { Button } from "@/components/ui/button";
import { type Appearance, useAppearance } from "@/lib/appearance";

const options: Array<{
  icon: typeof Sun;
  label: string;
  value: Appearance;
}> = [
  { icon: Monitor, label: "System", value: "system" },
  { icon: Sun, label: "Light", value: "light" },
  { icon: Moon, label: "Dark", value: "dark" },
];

export function AppearanceSelector() {
  const { appearance, updateAppearance } = useAppearance();

  return (
    <fieldset>
      <legend className="sr-only">Appearance</legend>
      <div className="flex flex-wrap gap-2">
        {options.map(({ icon: Icon, label, value }) => (
          <Button
            aria-pressed={appearance === value}
            key={value}
            onClick={() => updateAppearance(value)}
            type="button"
            variant={appearance === value ? "default" : "outline"}
          >
            <Icon aria-hidden="true" className="size-4" />
            {label}
          </Button>
        ))}
      </div>
    </fieldset>
  );
}
