// Build the OpenAPI document dynamically so the docs always point at the current local server URL.
module.exports = function buildDeveloperApiSpec(baseUrl) {
  return {
    openapi: "3.0.3",
    info: {
      title: "Alumni Featured Profile Developer API",
      version: "1.0.0",
      description:
        "Bearer-token-protected developer API for accessing the currently highlighted featured alumnus selected by the daily blind bidding workflow.",
    },
    servers: [
      {
        url: baseUrl,
        description: "Local coursework server",
      },
    ],
    tags: [
      {
        name: "FeaturedAlumnus",
        description: "Endpoints that expose the daily featured alumnus to approved external clients.",
      },
    ],
    components: {
      securitySchemes: {
        BearerAuth: {
          type: "http",
          scheme: "bearer",
          bearerFormat: "API Token",
          description:
            "Provide a bearer token created in the developer portal. Tokens currently use the featured:read scope and expire automatically after the configured token lifetime.",
        },
      },
      schemas: {
        FeaturedAlumnusResponse: {
          type: "object",
          properties: {
            success: { type: "boolean", example: true },
            data: {
              type: "object",
              properties: {
                featuredDate: { type: "string", example: "2026-03-13" },
                featureMonthKey: { type: "string", example: "2026-03" },
                featureMonthLabel: { type: "string", example: "March 2026" },
                alumni: {
                  type: "object",
                  properties: {
                    fullName: { type: "string", example: "Jane Doe" },
                    email: {
                      type: "string",
                      example: "jane.doe@iit.ac.lk",
                    },
                    contactNumber: {
                      type: "string",
                      example: "+94 77 123 4567",
                    },
                    professionalHeadline: {
                      type: "string",
                      example: "Senior Software Engineer",
                    },
                    biography: {
                      type: "string",
                      example: "Alumna working in cloud engineering and developer platforms.",
                    },
                    linkedinUrl: {
                      type: "string",
                      example: "https://www.linkedin.com/in/jane-doe",
                    },
                    location: { type: "string", example: "Colombo" },
                    profileImageUrl: {
                      type: "string",
                      example: "/uploads/profiles/example.png",
                    },
                    selectedAt: {
                      type: "string",
                      format: "date-time",
                      example: "2026-03-13T00:00:00.000Z",
                    },
                    degrees: {
                      type: "array",
                      items: {
                        type: "object",
                        properties: {
                          title: { type: "string", example: "BSc (Hons) Computer Science" },
                          institutionName: { type: "string", example: "University of Westminster" },
                          officialUrl: {
                            type: "string",
                            example: "https://www.westminster.ac.uk/computing",
                          },
                          completionDate: { type: "string", example: "2024-06-01" },
                        },
                      },
                    },
                    certifications: {
                      type: "array",
                      items: {
                        type: "object",
                        properties: {
                          title: { type: "string", example: "AWS Certified Developer" },
                          providerName: { type: "string", example: "Amazon Web Services" },
                          officialUrl: {
                            type: "string",
                            example: "https://aws.amazon.com/certification/",
                          },
                          completionDate: { type: "string", example: "2025-01-15" },
                        },
                      },
                    },
                    licenses: {
                      type: "array",
                      items: {
                        type: "object",
                        properties: {
                          title: { type: "string", example: "Professional Engineering Licence" },
                          awardingBody: { type: "string", example: "Engineering Council" },
                          officialUrl: {
                            type: "string",
                            example: "https://www.engc.org.uk/",
                          },
                          completionDate: { type: "string", example: "2025-03-20" },
                        },
                      },
                    },
                    courses: {
                      type: "array",
                      items: {
                        type: "object",
                        properties: {
                          title: { type: "string", example: "Advanced Cloud Architecture" },
                          providerName: { type: "string", example: "Coursera" },
                          officialUrl: {
                            type: "string",
                            example: "https://www.coursera.org/",
                          },
                          completionDate: { type: "string", example: "2024-11-02" },
                        },
                      },
                    },
                    employmentHistory: {
                      type: "array",
                      items: {
                        type: "object",
                        properties: {
                          employerName: { type: "string", example: "Phantasmagoria Ltd" },
                          jobTitle: { type: "string", example: "Platform Engineer" },
                          startDate: { type: "string", example: "2025-02-01" },
                          endDate: { type: "string", example: "" },
                        },
                      },
                    },
                  },
                },
              },
            },
          },
        },
        ErrorResponse: {
          type: "object",
          properties: {
            success: { type: "boolean", example: false },
            error: { type: "string", example: "Missing bearer token." },
          },
        },
      },
    },
    paths: {
      "/api/v1/featured-alumnus/today": {
        get: {
          tags: ["FeaturedAlumnus"],
          summary: "Get today's featured alumnus",
          description:
            "Returns the alumnus whose winning blind bid was activated for the current featured day.",
          security: [{ BearerAuth: [] }],
          responses: {
            "200": {
              description: "Featured alumnus returned successfully.",
              content: {
                "application/json": {
                  schema: {
                    $ref: "#/components/schemas/FeaturedAlumnusResponse",
                  },
                },
              },
            },
            "401": {
              description: "Missing, invalid, revoked, or expired bearer token.",
              content: {
                "application/json": {
                  schema: {
                    $ref: "#/components/schemas/ErrorResponse",
                  },
                },
              },
            },
            "403": {
              description: "Bearer token does not include the required scope.",
              content: {
                "application/json": {
                  schema: {
                    $ref: "#/components/schemas/ErrorResponse",
                  },
                },
              },
            },
            "404": {
              description: "No featured alumnus is available yet because today's featured day has not been resolved.",
              content: {
                "application/json": {
                  schema: {
                    $ref: "#/components/schemas/ErrorResponse",
                  },
                },
              },
            },
          },
        },
      },
    },
  };
};
