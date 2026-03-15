import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full text-sm font-medium transition-[color,box-shadow,background-color,border-color] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-[#ffcb71] focus-visible:ring-[#ffcb71]/30 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "border border-[#ffcb71] bg-[#ffcb71] text-[#0a0f18] shadow-[0_12px_30px_rgba(255,203,113,0.2)] hover:bg-[#ffd894]",
        destructive:
          "border border-red-400/30 bg-red-500/85 text-white shadow-[0_12px_30px_rgba(239,68,68,0.2)] hover:bg-red-500 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border border-white/15 bg-white/6 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.03)] hover:bg-white/12 hover:text-white",
        secondary:
          "border border-white/10 bg-white/10 text-white hover:bg-white/16",
        ghost: "text-[#d4dfec] hover:bg-white/8 hover:text-white",
        link: "text-[#ffcb71] underline-offset-4 hover:underline",
      },
      size: {
        default: "h-11 px-5 py-2 has-[>svg]:px-4",
        sm: "h-9 rounded-full px-4 has-[>svg]:px-3",
        lg: "h-12 rounded-full px-7 has-[>svg]:px-5",
        icon: "size-9",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
