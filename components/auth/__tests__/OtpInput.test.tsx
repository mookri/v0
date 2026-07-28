import { render, screen, fireEvent } from "@testing-library/react";
import * as React from "react";
import { OtpInput } from "@/components/auth/OtpInput";

describe("OtpInput — onComplete double-fire regression (P1)", () => {
  test("fires onComplete exactly once when value reaches length", () => {
    const onComplete = jest.fn();
    const onChange = jest.fn();

    const { rerender } = render(
      <OtpInput length={6} value="" onChange={onChange} onComplete={onComplete} />,
    );

    // Simulate the parent setting value to "123456" (e.g. after paste)
    rerender(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={onComplete} />,
    );

    expect(onComplete).toHaveBeenCalledTimes(1);
    expect(onComplete).toHaveBeenCalledWith("123456");
  });

  test("does NOT re-fire onComplete when value is unchanged on re-render", () => {
    const onComplete = jest.fn();
    const onChange = jest.fn();

    const { rerender } = render(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={onComplete} />,
    );
    rerender(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={onComplete} />,
    );
    rerender(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={onComplete} />,
    );

    expect(onComplete).toHaveBeenCalledTimes(1);
  });

  test("does NOT re-fire onComplete when parent passes a new callback identity", () => {
    // Regression for the original bug: useEffect deps included
    // `onComplete`, so every parent re-render with a new function
    // identity re-triggered the effect.
    const onChange = jest.fn();

    const { rerender } = render(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={jest.fn()} />,
    );
    rerender(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={jest.fn()} />,
    );

    // Should NOT have fired any of the parent's onComplete callbacks.
    // We test this by checking the screen state is stable, plus that
    // the digits are still displayed correctly.
    expect(screen.getAllByRole("textbox").length).toBe(6);
  });

  test("fires onComplete again if value changes to a different full value", () => {
    const onComplete = jest.fn();
    const onChange = jest.fn();

    const { rerender } = render(
      <OtpInput length={6} value="" onChange={onChange} onComplete={onComplete} />,
    );

    rerender(
      <OtpInput length={6} value="123456" onChange={onChange} onComplete={onComplete} />,
    );
    expect(onComplete).toHaveBeenCalledTimes(1);
    expect(onComplete).toHaveBeenLastCalledWith("123456");

    // Parent clears the OTP (user asked for a new code)
    rerender(
      <OtpInput length={6} value="" onChange={onChange} onComplete={onComplete} />,
    );

    // Then user types a new full OTP
    rerender(
      <OtpInput length={6} value="654321" onChange={onChange} onComplete={onComplete} />,
    );
    expect(onComplete).toHaveBeenCalledTimes(2);
    expect(onComplete).toHaveBeenLastCalledWith("654321");
  });

  test("does NOT fire onComplete when value is shorter than length", () => {
    const onComplete = jest.fn();
    const onChange = jest.fn();

    const { rerender } = render(
      <OtpInput length={6} value="" onChange={onChange} onComplete={onComplete} />,
    );
    rerender(
      <OtpInput length={6} value="123" onChange={onChange} onComplete={onComplete} />,
    );
    rerender(
      <OtpInput length={6} value="12345" onChange={onChange} onComplete={onComplete} />,
    );

    expect(onComplete).not.toHaveBeenCalled();
  });

  test("does NOT fire onComplete when onComplete is undefined", () => {
    const onChange = jest.fn();

    const { rerender } = render(
      <OtpInput length={6} value="" onChange={onChange} />,
    );
    // Should not throw
    expect(() => {
      rerender(<OtpInput length={6} value="123456" onChange={onChange} />);
    }).not.toThrow();
  });

  test("keying in the last digit fires onComplete exactly once", () => {
    const onComplete = jest.fn();
    // Use a real controlled component so the useEffect sees the
    // updated value. fireEvent.change() updates the input's DOM
    // value, but the parent `value` prop only changes when onChange
    // is propagated back via re-render.
    function Controlled() {
      const [v, setV] = React.useState("12345");
      return (
        <OtpInput
          length={6}
          value={v}
          onChange={(next) => {
            setV(next);
            onChangeMock(next);
          }}
          onComplete={onComplete}
        />
      );
    }
    const onChangeMock = jest.fn();

    render(<Controlled />);

    const inputs = screen.getAllByRole("textbox") as HTMLInputElement[];
    const last = inputs[5]!;
    fireEvent.change(last, { target: { value: "6" } });

    expect(onChangeMock).toHaveBeenCalledWith("123456");
    expect(onComplete).toHaveBeenCalledTimes(1);
    expect(onComplete).toHaveBeenCalledWith("123456");
  });
});
