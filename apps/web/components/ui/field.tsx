import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

interface FieldProps extends React.ComponentProps<typeof Input> {
  label: string;
  name: string;
  errors?: string[] | undefined;
}

function Field({ label, name, id = name, errors, ...props }: FieldProps) {
  const hasErrors = errors !== undefined && errors.length > 0;
  const errorId = `${id}-error`;

  return (
    <div className="flex flex-col gap-1.5">
      <Label htmlFor={id}>{label}</Label>
      <Input
        id={id}
        name={name}
        aria-describedby={hasErrors ? errorId : undefined}
        aria-invalid={hasErrors || undefined}
        {...props}
      />
      {hasErrors ? (
        <p id={errorId} role="alert" className="text-sm text-destructive">
          {errors[0]}
        </p>
      ) : null}
    </div>
  );
}

export { Field };
