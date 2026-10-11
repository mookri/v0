import React from "react";
import { render, screen, fireEvent, waitFor, act } from "@testing-library/react";
import { SearchBox } from "@/components/search/search-box";
import "@testing-library/jest-dom";

const push = jest.fn();
jest.mock("next/navigation", () => ({
  useRouter: () => ({ push: (...args: unknown[]) => push(...args) }),
}));

const SUGGESTIONS = {
  data: {
    query: "griha",
    suggestions: [
      {
        type: "puja_service",
        label: "Puja Services",
        items: [{ title: "Griha Pravesh", url: "/pujas/griha-pravesh", city: "Delhi" }],
      },
      {
        type: "temple",
        label: "Temples",
        items: [{ title: "Griha Vastu Temple", url: "/temples/griha-vastu-temple" }],
      },
    ],
  },
};

function mockFetchOnce(body: unknown, ok = true) {
  (global.fetch as jest.Mock).mockResolvedValueOnce({
    ok,
    json: async () => body,
  });
}

describe("SearchBox", () => {
  beforeEach(() => {
    push.mockClear();
    global.fetch = jest.fn();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  it("does not fetch suggestions below two characters", async () => {
    jest.useFakeTimers();
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: "g" } });
    await act(async () => {
      jest.advanceTimersByTime(500);
    });

    expect(global.fetch).not.toHaveBeenCalled();
    expect(screen.queryByRole("listbox")).not.toBeInTheDocument();
  });

  it("fetches (debounced) and renders grouped suggestions", async () => {
    jest.useFakeTimers();
    mockFetchOnce(SUGGESTIONS);
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: "griha" } });
    // Debounce: one keystroke, one fetch after the window elapses.
    expect(global.fetch).not.toHaveBeenCalled();
    await act(async () => {
      jest.advanceTimersByTime(300);
    });

    expect(global.fetch).toHaveBeenCalledTimes(1);
    expect(String(global.fetch.mock.calls[0][0])).toContain("/api/search/suggest?q=griha");

    const listbox = await screen.findByRole("listbox");
    expect(listbox).toBeInTheDocument();
    expect(screen.getByText("Puja Services")).toBeInTheDocument();
    expect(screen.getByText("Griha Pravesh")).toBeInTheDocument();
    expect(screen.getByText("Delhi")).toBeInTheDocument();
  });

  it("submits the query to /search on Enter", async () => {
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: " diwali " } });
    fireEvent.keyDown(input, { key: "Enter", code: "Enter" });

    expect(push).toHaveBeenCalledWith("/search?q=diwali");
  });

  it("navigates to a suggestion's URL on click", async () => {
    jest.useFakeTimers();
    mockFetchOnce(SUGGESTIONS);
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: "griha" } });
    await act(async () => {
      jest.advanceTimersByTime(300);
    });

    fireEvent.click(screen.getByText("Griha Pravesh"));
    expect(push).toHaveBeenCalledWith("/pujas/griha-pravesh");
    expect(screen.queryByRole("listbox")).not.toBeInTheDocument();
  });

  it("selects the highlighted suggestion with ArrowDown + Enter", async () => {
    jest.useFakeTimers();
    mockFetchOnce(SUGGESTIONS);
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: "griha" } });
    await act(async () => {
      jest.advanceTimersByTime(300);
    });

    fireEvent.keyDown(input, { key: "ArrowDown", code: "ArrowDown" });
    expect(screen.getByRole("option", { name: /Griha Pravesh/ })).toHaveAttribute(
      "aria-selected",
      "true",
    );

    fireEvent.keyDown(input, { key: "Enter", code: "Enter" });
    expect(push).toHaveBeenCalledWith("/pujas/griha-pravesh");
  });

  it("falls back to the plain search page when suggest fails", async () => {
    jest.useFakeTimers();
    mockFetchOnce({}, false);
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: "griha" } });
    await act(async () => {
      jest.advanceTimersByTime(300);
    });

    expect(screen.queryByRole("listbox")).not.toBeInTheDocument();
    // The form still submits — degraded, not broken.
    fireEvent.keyDown(input, { key: "Enter", code: "Enter" });
    expect(push).toHaveBeenCalledWith("/search?q=griha");
  });

  it("survives a network error without throwing", async () => {
    jest.useFakeTimers();
    (global.fetch as jest.Mock).mockRejectedValueOnce(new Error("offline"));
    render(<SearchBox />);
    const input = screen.getByRole("combobox", { name: "Search" });

    fireEvent.change(input, { target: { value: "griha" } });
    await act(async () => {
      jest.advanceTimersByTime(300);
    });

    expect(screen.queryByRole("listbox")).not.toBeInTheDocument();
  });

  it("seeds the input from initialQuery", async () => {
    render(<SearchBox initialQuery="navagraha" />);
    await waitFor(() => {
      expect(screen.getByRole("combobox", { name: "Search" })).toHaveValue("navagraha");
    });
  });
});
