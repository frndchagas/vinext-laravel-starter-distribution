import { cn } from "@/lib/utils";

interface LabelProps extends React.ComponentProps<"label"> {
  htmlFor: string;
}

function Label({ className, htmlFor, ...props }: LabelProps) {
  return (
    <label
      data-slot="label"
      htmlFor={htmlFor}
      className={cn(
        "text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase select-none",
        className,
      )}
      {...props}
    />
  );
}

export { Label };
