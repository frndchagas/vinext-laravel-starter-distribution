import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

import Home from "./page";

describe("starter home", () => {
  it("presents the architecture and the baseline decisions", () => {
    render(<Home />);

    expect(
      screen.getByRole("heading", { level: 1, name: "Vinext AI Starter for Laravel" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("heading", { name: "The baseline settles the recurring decisions." }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("A serious Laravel starting point for coding agents."),
    ).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "View architecture" })).toHaveAttribute(
      "href",
      "#architecture",
    );
    expect(screen.getAllByRole("article")).toHaveLength(4);
    expect(screen.getByText("One command, objective gates")).toBeInTheDocument();
  });
});
